<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Message;
use App\Entity\Professional;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\MessageRepository;
use App\Repository\ProfessionalRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\UserError;

final class MessageResolver
{
    public function __construct(
        private readonly MessageRepository $messageRepo,
        private readonly UserRepository $userRepo,
        private readonly ProfessionalRepository $professionalRepo,
        private readonly AppointmentRepository $appointmentRepo,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getMyConversations(User|Professional|Admin|null $currentUser): array
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        if ($currentUser instanceof User) {
            return $this->messageRepo->findConversationsForUser($currentUser);
        }

        if ($currentUser instanceof Professional) {
            return $this->messageRepo->findConversationsForProfessional($currentUser);
        }

        throw new UserError('Admins do not have conversations.');
    }

    public function getThread(array $args, User|Professional|Admin|null $currentUser): array
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        $contactId = (int) $args['contactId'];

        if ($currentUser instanceof User) {
            $professional = $this->professionalRepo->find($contactId);
            if (!$professional) {
                throw new UserError('Professional not found.');
            }

            return $this->messageRepo->findThread($currentUser, $professional);
        }

        if ($currentUser instanceof Professional) {
            $user = $this->userRepo->find($contactId);
            if (!$user) {
                throw new UserError('User not found.');
            }

            return $this->messageRepo->findThread($user, $currentUser);
        }

        throw new UserError('Admins cannot access message threads directly.');
    }

    public function sendMessage(array $args, User|Professional|Admin|null $currentUser): Message
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        if ($currentUser instanceof Admin) {
            throw new UserError('Admins cannot send messages.');
        }

        $input = $args['input'];
        $content = trim($input['content'] ?? '');
        if (strlen($content) < 1 || strlen($content) > 5000) {
            throw new UserError('Message content must be between 1 and 5000 characters.');
        }

        $appointment = $this->appointmentRepo->find($input['appointmentId']);
        if (!$appointment) {
            throw new UserError('Appointment not found.');
        }

        $message = new Message();
        $message->setContent($content);
        $message->setAppointment($appointment);

        if ($currentUser instanceof User) {
            if ($appointment->getUser()->getId() !== $currentUser->getId()) {
                throw new UserError('Access denied.');
            }
            $message->setSenderUser($currentUser);
            $message->setSenderRole(Message::FROM_USER);
            $message->setRecipientProfessional($appointment->getProfessional());
            $message->setRecipientRole(Message::FROM_PROFESSIONAL);
        } else {
            /** @var Professional $currentUser */
            if ($appointment->getProfessional()->getId() !== $currentUser->getId()) {
                throw new UserError('Access denied.');
            }
            $message->setSenderProfessional($currentUser);
            $message->setSenderRole(Message::FROM_PROFESSIONAL);
            $message->setRecipientUser($appointment->getUser());
            $message->setRecipientRole(Message::FROM_USER);
        }

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    public function markMessageRead(array $args, User|Professional|Admin|null $currentUser): Message
    {
        if (!$currentUser) {
            throw new UserError('Authentication required.');
        }

        $message = $this->messageRepo->find($args['id']);
        if (!$message) {
            throw new UserError('Message not found.');
        }

        // Only the recipient can mark as read
        $isRecipient = false;
        if ($currentUser instanceof User && $message->getRecipientUser()?->getId() === $currentUser->getId()) {
            $isRecipient = true;
        } elseif ($currentUser instanceof Professional && $message->getRecipientProfessional()?->getId() === $currentUser->getId()) {
            $isRecipient = true;
        }

        if (!$isRecipient) {
            throw new UserError('Access denied.');
        }

        $message->setIsRead(true);
        $this->em->flush();

        return $message;
    }
}
