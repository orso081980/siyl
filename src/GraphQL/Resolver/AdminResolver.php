<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Appointment;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\AdminRepository;
use App\Repository\AppointmentRepository;
use App\Repository\MessageRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use GraphQL\Error\UserError;

final class AdminResolver
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly ProfessionalRepository $professionalRepo,
        private readonly AdminRepository $adminRepo,
        private readonly AppointmentRepository $appointmentRepo,
        private readonly MessageRepository $messageRepo,
    ) {
    }

    private function requireAdmin(User|Professional|Admin|null $currentUser): void
    {
        if (!$currentUser instanceof Admin) {
            throw new UserError('Admin access required.');
        }
    }

    public function getAllUsers(array $args, User|Professional|Admin|null $currentUser): array
    {
        $this->requireAdmin($currentUser);
        $page = max(1, (int) ($args['page'] ?? 1));
        $limit = min(100, max(1, (int) ($args['limit'] ?? 20)));

        return $this->userRepo->findAllPaginated($page, $limit);
    }

    public function getAllProfessionals(array $args, User|Professional|Admin|null $currentUser): array
    {
        $this->requireAdmin($currentUser);
        $page = max(1, (int) ($args['page'] ?? 1));
        $limit = min(100, max(1, (int) ($args['limit'] ?? 20)));

        return $this->professionalRepo->findAllFiltered(null, null, null, null, $page, $limit);
    }

    public function getAllAdmins(array $args, User|Professional|Admin|null $currentUser): array
    {
        $this->requireAdmin($currentUser);

        return $this->adminRepo->findAll();
    }

    public function getAllAppointments(array $args, User|Professional|Admin|null $currentUser): array
    {
        $this->requireAdmin($currentUser);
        $page = max(1, (int) ($args['page'] ?? 1));
        $limit = min(100, max(1, (int) ($args['limit'] ?? 20)));

        return $this->appointmentRepo->findAllPaginated($page, $limit);
    }

    public function getStats(User|Professional|Admin|null $currentUser): array
    {
        $this->requireAdmin($currentUser);

        return [
            'totalUsers' => $this->userRepo->countAll(),
            'totalProfessionals' => $this->professionalRepo->countAll(),
            'totalAdmins' => $this->adminRepo->countAll(),
            'totalAppointments' => $this->appointmentRepo->countAll(),
            'totalMessages' => $this->messageRepo->countAll(),
            'pendingAppointments' => $this->appointmentRepo->countByStatus(Appointment::STATUS_PENDING),
            'confirmedAppointments' => $this->appointmentRepo->countByStatus(Appointment::STATUS_CONFIRMED),
            'completedAppointments' => $this->appointmentRepo->countByStatus(Appointment::STATUS_COMPLETED),
            'cancelledAppointments' => $this->appointmentRepo->countByStatus(Appointment::STATUS_CANCELLED),
        ];
    }
}
