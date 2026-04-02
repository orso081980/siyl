<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * Returns the appointment if it exists, belongs to the user, and is completed.
     * Returns null otherwise (caller should throw a UserError).
     */
    public function findCompletedAppointmentForReview(User $user, int $appointmentId): ?Appointment
    {
        $em = $this->getEntityManager();

        /** @var Appointment|null $appointment */
        $appointment = $em->getRepository(Appointment::class)->find($appointmentId);

        if (
            !$appointment ||
            $appointment->getUser()->getId() !== $user->getId() ||
            $appointment->getStatus() !== Appointment::STATUS_COMPLETED
        ) {
            return null;
        }

        return $appointment;
    }
}
