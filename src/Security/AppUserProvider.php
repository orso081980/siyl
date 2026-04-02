<?php

namespace App\Security;

use App\Repository\AdminRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provides authentication for Users, Professionals, and Admins by email.
 * The JWT token must contain the user's email as identifier.
 */
class AppUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly ProfessionalRepository $professionalRepository,
        private readonly AdminRepository $adminRepository,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Try each entity type in order: admin first (highest privilege), then professional, then user
        $user = $this->adminRepository->findByEmail($identifier)
            ?? $this->professionalRepository->findByEmail($identifier)
            ?? $this->userRepository->findByEmail($identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('No account found for email "%s"', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return in_array($class, [
            \App\Entity\User::class,
            \App\Entity\Professional::class,
            \App\Entity\Admin::class,
        ], true);
    }
}
