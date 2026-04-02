<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Professional;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Get all conversation partners for a user.
     */
    public function findConversationsForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.senderUser = :user OR m.recipientUser = :user')
            ->setParameter('user', $user)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all conversation partners for a professional.
     */
    public function findConversationsForProfessional(Professional $professional): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.senderProfessional = :pro OR m.recipientProfessional = :pro')
            ->setParameter('pro', $professional)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get thread between a user and a professional.
     */
    public function findThread(User $user, Professional $professional): array
    {
        return $this->createQueryBuilder('m')
            ->where(
                '(m.senderUser = :user AND m.recipientProfessional = :pro) OR '.
                '(m.senderProfessional = :pro AND m.recipientUser = :user)'
            )
            ->setParameter('user', $user)
            ->setParameter('pro', $professional)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
