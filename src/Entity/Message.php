<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
#[ORM\Index(columns: ['recipient_user_id', 'created_at'])]
#[ORM\Index(columns: ['recipient_professional_id', 'created_at'])]
class Message
{
    public const FROM_USER = 'user';
    public const FROM_PROFESSIONAL = 'professional';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $senderUser = null;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Professional $senderProfessional = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::FROM_USER, self::FROM_PROFESSIONAL])]
    private string $senderRole;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'recipient_user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $recipientUser = null;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'recipient_professional_id', nullable: true, onDelete: 'SET NULL')]
    private ?Professional $recipientProfessional = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::FROM_USER, self::FROM_PROFESSIONAL])]
    private string $recipientRole;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5000)]
    private string $content;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    #[ORM\ManyToOne(targetEntity: Appointment::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Appointment $appointment = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSenderUser(): ?User
    {
        return $this->senderUser;
    }

    public function setSenderUser(?User $senderUser): static
    {
        $this->senderUser = $senderUser;

        return $this;
    }

    public function getSenderProfessional(): ?Professional
    {
        return $this->senderProfessional;
    }

    public function setSenderProfessional(?Professional $senderProfessional): static
    {
        $this->senderProfessional = $senderProfessional;

        return $this;
    }

    public function getSenderRole(): string
    {
        return $this->senderRole;
    }

    public function setSenderRole(string $senderRole): static
    {
        $this->senderRole = $senderRole;

        return $this;
    }

    public function getRecipientUser(): ?User
    {
        return $this->recipientUser;
    }

    public function setRecipientUser(?User $recipientUser): static
    {
        $this->recipientUser = $recipientUser;

        return $this;
    }

    public function getRecipientProfessional(): ?Professional
    {
        return $this->recipientProfessional;
    }

    public function setRecipientProfessional(?Professional $recipientProfessional): static
    {
        $this->recipientProfessional = $recipientProfessional;

        return $this;
    }

    public function getRecipientRole(): string
    {
        return $this->recipientRole;
    }

    public function setRecipientRole(string $recipientRole): static
    {
        $this->recipientRole = $recipientRole;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Returns the sender entity (User or Professional).
     */
    public function getSender(): User|Professional|null
    {
        return self::FROM_USER === $this->senderRole ? $this->senderUser : $this->senderProfessional;
    }

    /**
     * Returns the recipient entity (User or Professional).
     */
    public function getRecipient(): User|Professional|null
    {
        return self::FROM_USER === $this->recipientRole ? $this->recipientUser : $this->recipientProfessional;
    }
}
