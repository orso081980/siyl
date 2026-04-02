<?php

namespace App\GraphQL;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * Central registry to avoid circular type dependencies.
 * Types are created lazily on first access.
 *
 * Registered as a Symfony service so it can be injected and mocked in tests.
 */
final class TypeRegistry
{
    private array $types = [];

    public function review(): ObjectType
    {
        return $this->get('Review', fn () => new ObjectType([
            'name' => 'Review',
            'fields' => function () {
                return [
                    'id'        => Type::nonNull(Type::int()),
                    'rating'    => Type::nonNull(Type::int()),
                    'comment'   => Type::string(),
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'user'      => [
                        'type' => Type::nonNull($this->user()),
                        'resolve' => fn ($obj) => $obj->getUser(),
                    ],
                ];
            },
        ]));
    }

    public function user(): ObjectType
    {
        return $this->get('User', fn () => new ObjectType([
            'name' => 'User',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'firstName' => Type::nonNull(Type::string()),
                    'lastName' => Type::nonNull(Type::string()),
                    'username' => Type::nonNull(Type::string()),
                    'email' => Type::nonNull(Type::string()),
                    'phone' => Type::string(),
                    'languages' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'avatar' => Type::string(),
                    'role' => Type::nonNull(Type::string()),
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function service(): ObjectType
    {
        return $this->get('Service', fn () => new ObjectType([
            'name' => 'Service',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'name' => Type::nonNull(Type::string()),
                    'description' => Type::string(),
                    'cost' => Type::nonNull(Type::float()),
                    'duration' => Type::int(),
                    'languages' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function professional(): ObjectType
    {
        return $this->get('Professional', fn () => new ObjectType([
            'name' => 'Professional',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'firstName' => Type::nonNull(Type::string()),
                    'lastName' => Type::nonNull(Type::string()),
                    'username' => Type::nonNull(Type::string()),
                    'email' => Type::nonNull(Type::string()),
                    'businessName' => Type::string(),
                    'job' => Type::nonNull(Type::string()),
                    'description' => Type::string(),
                    'phone' => Type::string(),
                    'languages'         => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'location'          => Type::string(),
                    'verified'          => ['type' => Type::nonNull(Type::boolean()), 'resolve' => fn ($obj) => $obj->isVerified()],
                    'yearsOfExperience' => Type::int(),
                    'videoUrl'          => Type::string(),
                    'degrees'           => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'areasOfExpertise'  => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'whoIWorkWith'       => Type::string(),
                    'specialities'      => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                    'role'              => Type::nonNull(Type::string()),
                    'services'          => Type::nonNull(Type::listOf(Type::nonNull($this->service()))),
                    'reviews'           => Type::nonNull(Type::listOf(Type::nonNull($this->review()))),
                    'reviewsAverage'    => ['type' => Type::float(), 'resolve' => fn ($obj) => $obj->getReviewsAverage()],
                    'reviewsCount'      => ['type' => Type::nonNull(Type::int()), 'resolve' => fn ($obj) => $obj->getReviews()->count()],
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function admin(): ObjectType
    {
        return $this->get('Admin', fn () => new ObjectType([
            'name' => 'Admin',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'firstName' => Type::nonNull(Type::string()),
                    'lastName' => Type::nonNull(Type::string()),
                    'email' => Type::nonNull(Type::string()),
                    'role' => Type::nonNull(Type::string()),
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function appointment(): ObjectType
    {
        return $this->get('Appointment', fn () => new ObjectType([
            'name' => 'Appointment',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'professional' => Type::nonNull($this->professional()),
                    'user' => Type::nonNull($this->user()),
                    'service' => $this->service(),
                    'serviceName' => Type::nonNull(Type::string()),
                    'date' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getDate()->format(\DateTimeInterface::ATOM)],
                    'status' => Type::nonNull(Type::string()),
                    'notes' => Type::string(),
                    'messages'  => Type::nonNull(Type::listOf(Type::nonNull($this->message()))),
                    'hasReview' => ['type' => Type::nonNull(Type::boolean()), 'resolve' => fn ($obj) => $obj->getReview() !== null],
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function message(): ObjectType
    {
        return $this->get('Message', fn () => new ObjectType([
            'name' => 'Message',
            'fields' => function () {
                return [
                    'id' => Type::nonNull(Type::int()),
                    'senderRole' => Type::nonNull(Type::string()),
                    'recipientRole' => Type::nonNull(Type::string()),
                    'content' => Type::nonNull(Type::string()),
                    'isRead' => ['type' => Type::nonNull(Type::boolean()), 'resolve' => fn ($obj) => $obj->isRead()],
                    'appointment' => $this->appointment(),
                    'createdAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getCreatedAt()->format(\DateTimeInterface::ATOM)],
                    'updatedAt' => ['type' => Type::nonNull(Type::string()), 'resolve' => fn ($obj) => $obj->getUpdatedAt()->format(\DateTimeInterface::ATOM)],
                ];
            },
        ]));
    }

    public function authPayload(): ObjectType
    {
        return $this->get('AuthPayload', fn () => new ObjectType([
            'name' => 'AuthPayload',
            'fields' => [
                'token' => Type::nonNull(Type::string()),
                'role' => Type::nonNull(Type::string()),
                'email' => Type::nonNull(Type::string()),
            ],
        ]));
    }

    public function stats(): ObjectType
    {
        return $this->get('Stats', fn () => new ObjectType([
            'name' => 'Stats',
            'fields' => [
                'totalUsers' => Type::nonNull(Type::int()),
                'totalProfessionals' => Type::nonNull(Type::int()),
                'totalAdmins' => Type::nonNull(Type::int()),
                'totalAppointments' => Type::nonNull(Type::int()),
                'totalMessages' => Type::nonNull(Type::int()),
                'pendingAppointments' => Type::nonNull(Type::int()),
                'confirmedAppointments' => Type::nonNull(Type::int()),
                'completedAppointments' => Type::nonNull(Type::int()),
                'cancelledAppointments' => Type::nonNull(Type::int()),
            ],
        ]));
    }

    public function professionalConnection(): ObjectType
    {
        return $this->get('ProfessionalConnection', fn () => new ObjectType([
            'name' => 'ProfessionalConnection',
            'fields' => function () {
                return [
                    'items' => Type::nonNull(Type::listOf(Type::nonNull($this->professional()))),
                    'total' => Type::nonNull(Type::int()),
                ];
            },
        ]));
    }

    private function get(string $name, callable $factory): ObjectType
    {
        if (!isset($this->types[$name])) {
            $this->types[$name] = $factory();
        }

        return $this->types[$name];
    }
}
