<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\AdminRepository;
use App\Service\PasswordPolicy;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\UserError;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * GraphQL mutations that remain server-side: admin creation only.
 *
 * Login / register are intentionally NOT exposed through GraphQL — they are
 * handled by AuthController (REST) which manages the HttpOnly refresh-token
 * cookie lifecycle. Keeping auth out of GraphQL prevents bypassing the cookie
 * strategy and reduces the GraphQL attack surface.
 */
final class AuthResolver
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminRepository $adminRepo,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface $jwtManager,
    ) {
    }

    public function createAdmin(array $args, User|Professional|Admin|null $currentUser): array
    {
        if (!$currentUser instanceof Admin) {
            throw new UserError('Only admins can create admin accounts.');
        }

        $input = $args['input'];

        if ($this->adminRepo->findByEmail($input['email'])) {
            throw new UserError('Email already in use.');
        }

        $passwordError = PasswordPolicy::validate($input['password']);
        if ($passwordError) {
            throw new UserError($passwordError);
        }

        $admin = new Admin();
        $admin->setFirstName($input['firstName']);
        $admin->setLastName($input['lastName']);
        $admin->setEmail($input['email']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, $input['password']));

        $this->em->persist($admin);
        $this->em->flush();

        return ['token' => $this->jwtManager->create($admin), 'role' => $admin->getRole(), 'email' => $admin->getEmail()];
    }
}
