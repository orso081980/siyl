<?php

namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_refresh_tokens_expires')]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** 64-char hex string (256-bit entropy) */
    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $token, string $email, string $role, \DateTimeImmutable $expiresAt)
    {
        $this->token     = $token;
        $this->email     = $email;
        $this->role      = $role;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }
    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }
    public function isExpired(): bool { return $this->expiresAt <= new \DateTimeImmutable(); }
}
