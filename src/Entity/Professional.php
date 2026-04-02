<?php

namespace App\Entity;

use App\Repository\ProfessionalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProfessionalRepository::class)]
#[ORM\Table(name: 'professionals')]
#[ORM\UniqueConstraint(name: 'professional_email_unique', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'professional_username_unique', columns: ['username'])]
class Professional implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    private string $username;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $businessName = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private string $job;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'json')]
    private array $languages = [];

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean')]
    private bool $verified = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $yearsOfExperience = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $videoUrl = null;

    #[ORM\Column(type: 'json')]
    private array $degrees = [];

    #[ORM\Column(type: 'json')]
    private array $areasOfExpertise = [];

    #[ORM\Column(type: 'text', nullable: true, name: 'who_i_work_with')]
    private ?string $whoIWorkWith = null;

    #[ORM\Column(type: 'json')]
    private array $specialities = [];

    #[ORM\Column(type: 'string', length: 20)]
    private string $role = 'professional';

    #[ORM\OneToMany(targetEntity: Service::class, mappedBy: 'professional', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $services;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'professional', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reviews;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->reviews  = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = strtolower($username);

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower($email);

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    public function setBusinessName(?string $businessName): static
    {
        $this->businessName = $businessName;

        return $this;
    }

    public function getJob(): string
    {
        return $this->job;
    }

    public function setJob(string $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getLanguages(): array
    {
        return $this->languages;
    }

    public function setLanguages(array $languages): static
    {
        $this->languages = $languages;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getYearsOfExperience(): ?int
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(?int $yearsOfExperience): static
    {
        $this->yearsOfExperience = $yearsOfExperience;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getDegrees(): array
    {
        return $this->degrees;
    }

    public function setDegrees(array $degrees): static
    {
        $this->degrees = $degrees;

        return $this;
    }

    public function getAreasOfExpertise(): array
    {
        return $this->areasOfExpertise;
    }

    public function setAreasOfExpertise(array $areasOfExpertise): static
    {
        $this->areasOfExpertise = $areasOfExpertise;

        return $this;
    }

    public function getWhoIWorkWith(): ?string
    {
        return $this->whoIWorkWith;
    }

    public function setWhoIWorkWith(?string $whoIWorkWith): static
    {
        $this->whoIWorkWith = $whoIWorkWith;

        return $this;
    }

    public function getSpecialities(): array
    {
        return $this->specialities;
    }

    public function setSpecialities(array $specialities): static
    {
        $this->specialities = $specialities;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): static
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
            $service->setProfessional($this);
        }

        return $this;
    }

    public function removeService(Service $service): static
    {
        $this->services->removeElement($service);

        return $this;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getReviewsAverage(): ?float
    {
        if ($this->reviews->isEmpty()) {
            return null;
        }
        $sum = array_sum($this->reviews->map(fn (Review $r) => $r->getRating())->toArray());

        return round($sum / $this->reviews->count(), 1);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // UserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_PROFESSIONAL'];
    }

    public function eraseCredentials(): void
    {
    }
}
