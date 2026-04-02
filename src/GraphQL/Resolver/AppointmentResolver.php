<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Appointment;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\UserError;

final class AppointmentResolver
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepo,
        private readonly ProfessionalRepository $professionalRepo,
        private readonly ServiceRepository $serviceRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getMyAppointments(array $args, User|Professional|Admin|null $currentUser): array
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        $page = max(1, (int) ($args['page'] ?? 1));
        $limit = min(50, max(1, (int) ($args['limit'] ?? 20)));

        if ($currentUser instanceof User) {
            return $this->appointmentRepo->findByUser($currentUser, $page, $limit);
        }

        if ($currentUser instanceof Professional) {
            return $this->appointmentRepo->findByProfessional($currentUser, $page, $limit);
        }

        // Admin sees all
        return $this->appointmentRepo->findAllPaginated($page, $limit);
    }

    public function getAppointment(array $args, User|Professional|Admin|null $currentUser): Appointment
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        $appointment = $this->appointmentRepo->find($args['id']);
        if (!$appointment) {
            throw new UserError('Appointment not found.');
        }

        // Access control: only owner, the assigned professional, or admin can view
        if (!$currentUser instanceof Admin) {
            $ownedByUser = $currentUser instanceof User && $appointment->getUser()->getId() === $currentUser->getId();
            $ownedByPro = $currentUser instanceof Professional && $appointment->getProfessional()->getId() === $currentUser->getId();
            if (!$ownedByUser && !$ownedByPro) {
                throw new UserError('Access denied.');
            }
        }

        return $appointment;
    }

    public function createAppointment(array $args, User|Professional|Admin|null $currentUser): Appointment
    {
        if (!$currentUser instanceof User) {
            throw new UserError('Authentication required. Must be a regular user.');
        }

        $input = $args['input'];

        $professional = $this->professionalRepo->find($input['professionalId']);
        if (!$professional) {
            throw new UserError('Professional not found.');
        }

        $service = null;
        if (!empty($input['serviceId'])) {
            $service = $this->serviceRepo->find($input['serviceId']);
            if (!$service || $service->getProfessional()->getId() !== $professional->getId()) {
                throw new UserError('Service not found or does not belong to this professional.');
            }
        }

        $appointment = new Appointment();
        $appointment->setUser($currentUser);
        $appointment->setProfessional($professional);
        $appointment->setService($service);
        $appointment->setServiceName($input['serviceName'] ?? ($service?->getName() ?? 'Consultation'));
        $appointment->setDate(new \DateTime($input['date']));
        $appointment->setNotes($input['notes'] ?? null);

        $this->em->persist($appointment);
        $this->em->flush();

        return $appointment;
    }

    public function updateAppointmentStatus(array $args, User|Professional|Admin|null $currentUser): Appointment
    {
        if (!$currentUser instanceof Professional && !$currentUser instanceof Admin) {
            throw new UserError('Only professionals or admins can update appointment status.');
        }

        $appointment = $this->appointmentRepo->find($args['id']);
        if (!$appointment) {
            throw new UserError('Appointment not found.');
        }

        if ($currentUser instanceof Professional && $appointment->getProfessional()->getId() !== $currentUser->getId()) {
            throw new UserError('Access denied.');
        }

        $validStatuses = [
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_COMPLETED,
        ];

        if (!in_array($args['status'], $validStatuses, true)) {
            throw new UserError('Invalid status value.');
        }

        $appointment->setStatus($args['status']);
        $this->em->flush();

        return $appointment;
    }
}
