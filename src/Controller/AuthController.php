<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\AdminRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use App\Service\PasswordPolicy;
use App\Service\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * REST endpoints for authentication.
 *
 * All write operations (login, register, refresh, logout) are plain REST — not GraphQL.
 * GraphQL is reserved for domain data queries/mutations.
 *
 * Cookie strategy:
 *   - Access token (JWT):   short-lived (see lexik config TTL), returned in JSON body → stored in
 *                           browser memory only (never localStorage, never a readable cookie).
 *   - Refresh token:        long-lived (7 days), set as HttpOnly + SameSite=Strict cookie →
 *                           invisible to JavaScript, sent automatically by the browser on /auth/*.
 *
 * On production, ensure the backend is served over HTTPS so the Secure flag is active.
 * If the SPA and API are on different eTLD+1 domains, change SameSite → None and enforce HTTPS.
 */
#[Route('/auth', name: 'auth_')]
final class AuthController extends AbstractController
{
    private const REFRESH_COOKIE = 'refresh_token';
    private const REFRESH_TTL    = 604800; // 7 days in seconds

    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly ProfessionalRepository $professionalRepo,
        private readonly AdminRepository $adminRepo,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenService $refreshTokenService,
    ) {}

    /**
     * POST /auth/login
     * Body: { "email": "...", "password": "...", "role": "user|professional|admin" (optional) }
     * Returns: { "token": "...", "role": "...", "email": "..." } + sets refresh cookie.
     */
    #[Route('/login', name: 'login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): JsonResponse
    {
        $data     = $this->parseJson($request);
        $email    = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $role     = $data['role'] ?? null;

        $account = $this->findAccount($email, $role);

        if (!$account || !$this->passwordHasher->isPasswordValid($account, $password)) {
            return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->buildAuthResponse($request, $account);
    }

    /**
     * POST /auth/register/user
     * Body: RegisterUserInput fields (firstName, lastName, email, password, ...)
     * Returns: { "token": "...", "role": "...", "email": "..." } + sets refresh cookie.
     */
    #[Route('/register/user', name: 'register_user', methods: ['POST', 'OPTIONS'])]
    public function registerUser(Request $request): JsonResponse
    {
        $data  = $this->parseJson($request);
        $email = strtolower(trim($data['email'] ?? ''));

        if ($this->userRepo->findByEmail($email)) {
            return $this->json(['error' => 'Email already in use.'], Response::HTTP_CONFLICT);
        }

        $username = $data['username'] ?? strtolower(($data['firstName'] ?? '') . ($data['lastName'] ?? ''));
        if ($this->userRepo->findByUsername($username)) {
            return $this->json(['error' => 'Username already in use.'], Response::HTTP_CONFLICT);
        }

        $passwordError = PasswordPolicy::validate($data['password'] ?? '');
        if ($passwordError) {
            return $this->json(['error' => $passwordError], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->setFirstName($data['firstName'] ?? '');
        $user->setLastName($data['lastName'] ?? '');
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password'] ?? ''));
        $user->setPhone($data['phone'] ?? null);
        $user->setLanguages($data['languages'] ?? []);

        $this->em->persist($user);
        $this->em->flush();

        return $this->buildAuthResponse($request, $user, Response::HTTP_CREATED);
    }

    /**
     * POST /auth/register/professional
     * Body: RegisterProfessionalInput fields (firstName, lastName, email, password, job, ...)
     * Returns: { "token": "...", "role": "...", "email": "..." } + sets refresh cookie.
     */
    #[Route('/register/professional', name: 'register_professional', methods: ['POST', 'OPTIONS'])]
    public function registerProfessional(Request $request): JsonResponse
    {
        $data  = $this->parseJson($request);
        $email = strtolower(trim($data['email'] ?? ''));

        if ($this->professionalRepo->findByEmail($email)) {
            return $this->json(['error' => 'Email already in use.'], Response::HTTP_CONFLICT);
        }

        $username = $data['username'] ?? strtolower(($data['firstName'] ?? '') . ($data['lastName'] ?? ''));
        if ($this->professionalRepo->findByUsername($username)) {
            return $this->json(['error' => 'Username already in use.'], Response::HTTP_CONFLICT);
        }

        $passwordError = PasswordPolicy::validate($data['password'] ?? '');
        if ($passwordError) {
            return $this->json(['error' => $passwordError], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $professional = new Professional();
        $professional->setFirstName($data['firstName'] ?? '');
        $professional->setLastName($data['lastName'] ?? '');
        $professional->setUsername($username);
        $professional->setEmail($email);
        $professional->setPassword($this->passwordHasher->hashPassword($professional, $data['password'] ?? ''));
        $professional->setJob($data['job'] ?? '');
        $professional->setBusinessName($data['businessName'] ?? null);
        $professional->setDescription($data['description'] ?? null);
        $professional->setPhone($data['phone'] ?? null);
        $professional->setLanguages($data['languages'] ?? []);

        $this->em->persist($professional);
        $this->em->flush();

        return $this->buildAuthResponse($request, $professional, Response::HTTP_CREATED);
    }

    /**
     * POST /auth/refresh
     * Reads the HttpOnly refresh_token cookie, validates it, issues a new short-lived JWT.
     * Rotates the refresh token (old is revoked, new is issued) to prevent replay attacks.
     */
    #[Route('/refresh', name: 'refresh', methods: ['POST', 'OPTIONS'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(self::REFRESH_COOKIE);

        if (!$refreshToken) {
            return $this->json(['error' => 'No session.'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = $this->refreshTokenService->validate($refreshToken);

        if (!$payload) {
            $response = $this->json(['error' => 'Session expired.'], Response::HTTP_UNAUTHORIZED);
            $this->clearRefreshCookie($response, $request);

            return $response;
        }

        $account = $this->findAccount($payload['email'], $payload['role']);

        if (!$account) {
            $this->refreshTokenService->revoke($refreshToken);
            $response = $this->json(['error' => 'Account not found.'], Response::HTTP_UNAUTHORIZED);
            $this->clearRefreshCookie($response, $request);

            return $response;
        }

        // Rotate: revoke old, issue new
        $this->refreshTokenService->revoke($refreshToken);

        return $this->buildAuthResponse($request, $account);
    }

    /**
     * POST /auth/logout
     * Revokes the refresh token and clears the cookie.
     */
    #[Route('/logout', name: 'logout', methods: ['POST', 'OPTIONS'])]
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(self::REFRESH_COOKIE);

        if ($refreshToken) {
            $this->refreshTokenService->revoke($refreshToken);
        }

        $response = $this->json(['status' => 'ok']);
        $this->clearRefreshCookie($response, $request);

        return $response;
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function findAccount(string $email, ?string $role): User|Professional|Admin|null
    {
        if ($role) {
            return match ($role) {
                'professional' => $this->professionalRepo->findByEmail($email),
                'admin'        => $this->adminRepo->findByEmail($email),
                default        => $this->userRepo->findByEmail($email),
            };
        }

        // Auto-detect order: user → professional → admin
        return $this->userRepo->findByEmail($email)
            ?? $this->professionalRepo->findByEmail($email)
            ?? $this->adminRepo->findByEmail($email);
    }

    private function buildAuthResponse(
        Request $request,
        User|Professional|Admin $account,
        int $statusCode = Response::HTTP_OK,
    ): JsonResponse {
        $accessToken     = $this->jwtManager->create($account);
        $newRefreshToken = $this->refreshTokenService->create(
            $account->getUserIdentifier(),
            $account->getRole(),
        );

        $response = $this->json([
            'token' => $accessToken,
            'role'  => $account->getRole(),
            'email' => $account->getUserIdentifier(),
        ], $statusCode);

        $cookie = Cookie::create(self::REFRESH_COOKIE)
            ->withValue($newRefreshToken)
            ->withExpires(time() + self::REFRESH_TTL)
            ->withPath('/')
            ->withSecure($request->isSecure())
            ->withHttpOnly(true)
            // SameSite=None when running over HTTPS (e.g. AWS API Gateway) so the cookie
            // is sent from a cross-origin SPA. SameSite=Strict for local HTTP dev.
            ->withSameSite($request->isSecure() ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_STRICT);

        $response->headers->setCookie($cookie);

        return $response;
    }

    private function clearRefreshCookie(JsonResponse $response, Request $request): void
    {
        $response->headers->clearCookie(
            self::REFRESH_COOKIE,
            '/',
            null,
            $request->isSecure(),
            true,
        );
    }

    private function parseJson(Request $request): array
    {
        try {
            $content = $request->getContent();

            return $content ? (json_decode($content, true, 512, JSON_THROW_ON_ERROR) ?? []) : [];
        } catch (\JsonException) {
            return [];
        }
    }
}
