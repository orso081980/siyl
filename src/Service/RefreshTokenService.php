<?php

namespace App\Service;

use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Persists refresh tokens in PostgreSQL so they survive across Lambda invocations.
 *
 * Each refresh token is a 256-bit random hex string. On rotation the old token is
 * deleted and a new one is inserted (single-use refresh tokens with revocation).
 */
final class RefreshTokenService
{
    /** 7 days in seconds */
    private const TTL = 604800;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RefreshTokenRepository $repo,
    ) {}

    /**
     * Creates a new refresh token tied to the given account and persists it.
     * Returns the raw token string to be set as a cookie value.
     */
    public function create(string $email, string $role): string
    {
        $token     = bin2hex(random_bytes(32)); // 256 bits → 64 hex chars
        $expiresAt = new \DateTimeImmutable('+' . self::TTL . ' seconds');

        $entity = new RefreshToken($token, $email, $role, $expiresAt);
        $this->em->persist($entity);
        $this->em->flush();

        return $token;
    }

    /**
     * Returns ['email' => ..., 'role' => ...] if the token is valid and not expired.
     * Returns null if missing, expired, or tampered with.
     */
    public function validate(string $token): ?array
    {
        if (!ctype_xdigit($token) || strlen($token) !== 64) {
            return null;
        }

        $entity = $this->repo->findValidByToken($token);

        if (!$entity) {
            return null;
        }

        return ['email' => $entity->getEmail(), 'role' => $entity->getRole()];
    }

    /**
     * Revokes a refresh token so it can no longer be used.
     */
    public function revoke(string $token): void
    {
        $entity = $this->repo->findOneBy(['token' => $token]);

        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
        }
    }
}

