<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\UserError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\PasswordPolicy;

final class UserResolver
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function getMyProfile(User|Professional|Admin|null $currentUser): User
    {
        if (!$currentUser instanceof User) {
            throw new UserError('Authentication required. Must be a regular user.');
        }

        return $currentUser;
    }

    public function updateProfile(array $args, User|Professional|Admin|null $currentUser): User
    {
        if (!$currentUser instanceof User) {
            throw new UserError('Authentication required. Must be a regular user.');
        }

        $input = $args['input'];
        if (isset($input['firstName'])) {
            $currentUser->setFirstName($input['firstName']);
        }
        if (isset($input['lastName'])) {
            $currentUser->setLastName($input['lastName']);
        }
        if (isset($input['phone'])) {
            $currentUser->setPhone($input['phone']);
        }
        if (isset($input['languages'])) {
            $currentUser->setLanguages($input['languages']);
        }
        if (isset($input['avatar'])) {
            $currentUser->setAvatar($input['avatar']);
        }

        if (isset($input['password'])) {
            $passwordError = PasswordPolicy::validate($input['password']);
            if ($passwordError) {
                throw new UserError($passwordError);
            }
            $currentUser->setPassword($this->passwordHasher->hashPassword($currentUser, $input['password']));
        }

        $this->em->flush();

        return $currentUser;
    }

    public function me(User|Professional|Admin|null $currentUser): User|Professional|Admin
    {
        if (!$currentUser) {
            throw new UserError('Not authenticated.');
        }

        return $currentUser;
    }
}
