<?php

namespace App\GraphQL;

use App\Entity\Admin;
use App\Entity\Professional;
use App\Entity\User;
use App\GraphQL\Resolver\AdminResolver;
use App\GraphQL\Resolver\AppointmentResolver;
use App\GraphQL\Resolver\AuthResolver;
use App\GraphQL\Resolver\MessageResolver;
use App\GraphQL\Resolver\ProfessionalResolver;
use App\GraphQL\Resolver\UserResolver;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Schema;
use Symfony\Bundle\SecurityBundle\Security;

final class SchemaBuilder
{
    public function __construct(
        private readonly TypeRegistry $types,
        private readonly AuthResolver $authResolver,
        private readonly UserResolver $userResolver,
        private readonly ProfessionalResolver $professionalResolver,
        private readonly AppointmentResolver $appointmentResolver,
        private readonly MessageResolver $messageResolver,
        private readonly AdminResolver $adminResolver,
        private readonly Security $security,
    ) {
    }

    public function build(): Schema
    {
        return new Schema([
            'query' => $this->buildQuery(),
            'mutation' => $this->buildMutation(),
        ]);
    }

    private function currentUser(): User|Professional|Admin|null
    {
        $user = $this->security->getUser();
        if ($user instanceof User || $user instanceof Professional || $user instanceof Admin) {
            return $user;
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // Input types
    // -----------------------------------------------------------------------

    private function createAdminInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'CreateAdminInput',
            'fields' => [
                'firstName' => Type::nonNull(Type::string()),
                'lastName' => Type::nonNull(Type::string()),
                'email' => Type::nonNull(Type::string()),
                'password' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    private function updateUserInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'UpdateUserInput',
            'fields' => [
                'firstName' => Type::string(),
                'lastName' => Type::string(),
                'phone' => Type::string(),
                'languages' => Type::listOf(Type::nonNull(Type::string())),
                'avatar' => Type::string(),
                'password' => Type::string(),
            ],
        ]);
    }

    private function updateProfessionalInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'UpdateProfessionalInput',
            'fields' => [
                'firstName'         => Type::string(),
                'lastName'          => Type::string(),
                'businessName'      => Type::string(),
                'job'               => Type::string(),
                'description'       => Type::string(),
                'phone'             => Type::string(),
                'languages'         => Type::listOf(Type::nonNull(Type::string())),
                'location'          => Type::string(),
                'yearsOfExperience' => Type::int(),
                'videoUrl'          => Type::string(),
                'degrees'           => Type::listOf(Type::nonNull(Type::string())),
                'areasOfExpertise'  => Type::listOf(Type::nonNull(Type::string())),
                'whoIWorkWith'      => Type::string(),
                'specialities'      => Type::listOf(Type::nonNull(Type::string())),
            ],
        ]);
    }

    private function createReviewInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'CreateReviewInput',
            'fields' => [
                'appointmentId'  => Type::nonNull(Type::int()),
                'rating'         => Type::nonNull(Type::int()),
                'comment'        => Type::string(),
            ],
        ]);
    }

    private function serviceInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'ServiceInput',
            'fields' => [
                'name' => Type::nonNull(Type::string()),
                'description' => Type::string(),
                'cost' => Type::nonNull(Type::float()),
                'duration' => Type::int(),
                'languages' => Type::listOf(Type::nonNull(Type::string())),
            ],
        ]);
    }

    private function updateServiceInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'UpdateServiceInput',
            'fields' => [
                'name' => Type::string(),
                'description' => Type::string(),
                'cost' => Type::float(),
                'duration' => Type::int(),
                'languages' => Type::listOf(Type::nonNull(Type::string())),
            ],
        ]);
    }

    private function createAppointmentInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'CreateAppointmentInput',
            'fields' => [
                'professionalId' => Type::nonNull(Type::int()),
                'serviceId' => Type::int(),
                'serviceName' => Type::string(),
                'date' => Type::nonNull(Type::string()),
                'notes' => Type::string(),
            ],
        ]);
    }

    private function sendMessageInput(): InputObjectType
    {
        return new InputObjectType([
            'name' => 'SendMessageInput',
            'fields' => [
                'appointmentId' => Type::nonNull(Type::int()),
                'content' => Type::nonNull(Type::string()),
            ],
        ]);
    }

    // -----------------------------------------------------------------------
    // MeResult union type: can return User | Professional | Admin
    // -----------------------------------------------------------------------

    private function meResultType(): UnionType
    {
        return new UnionType([
            'name' => 'MeResult',
            'types' => [$this->types->user(), $this->types->professional(), $this->types->admin()],
            'resolveType' => function ($value) {
                if ($value instanceof User) {
                    return $this->types->user();
                }
                if ($value instanceof Professional) {
                    return $this->types->professional();
                }
                if ($value instanceof Admin) {
                    return $this->types->admin();
                }

                return null;
            },
        ]);
    }

    // -----------------------------------------------------------------------
    // Query type
    // -----------------------------------------------------------------------

    private function buildQuery(): ObjectType
    {
        return new ObjectType([
            'name' => 'Query',
            'fields' => function () {
                return [
                    // Public
                    'professionals' => [
                        'type' => Type::nonNull($this->types->professionalConnection()),
                        'args' => [
                            'language' => Type::string(),
                            'search'   => Type::string(),
                            'job'      => Type::string(),
                            'location' => Type::string(),
                            'page'     => Type::int(),
                            'limit'    => Type::int(),
                        ],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->getProfessionals($args),
                    ],
                    'professionalJobs' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                        'resolve' => fn () => $this->professionalResolver->getJobs(),
                    ],
                    'professionalLocations' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                        'resolve' => fn () => $this->professionalResolver->getLocations(),
                    ],
                    'professional' => [
                        'type' => Type::nonNull($this->types->professional()),
                        'args' => ['id' => Type::nonNull(Type::int())],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->getProfessional($args),
                    ],
                    'professionalLanguages' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                        'resolve' => fn () => $this->professionalResolver->getLanguages(),
                    ],

                    // Auth required
                    'me' => [
                        'type' => $this->meResultType(),
                        'resolve' => fn () => $this->userResolver->me($this->currentUser()),
                    ],
                    'myUserProfile' => [
                        'type' => Type::nonNull($this->types->user()),
                        'resolve' => fn () => $this->userResolver->getMyProfile($this->currentUser()),
                    ],
                    'myProfessionalProfile' => [
                        'type' => Type::nonNull($this->types->professional()),
                        'resolve' => fn () => $this->professionalResolver->getMyProfile($this->currentUser()),
                    ],
                    'myAppointments' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->appointment()))),
                        'args' => ['page' => Type::int(), 'limit' => Type::int()],
                        'resolve' => fn ($root, $args) => $this->appointmentResolver->getMyAppointments($args, $this->currentUser()),
                    ],
                    'appointment' => [
                        'type' => Type::nonNull($this->types->appointment()),
                        'args' => ['id' => Type::nonNull(Type::int())],
                        'resolve' => fn ($root, $args) => $this->appointmentResolver->getAppointment($args, $this->currentUser()),
                    ],
                    'myConversations' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->message()))),
                        'resolve' => fn () => $this->messageResolver->getMyConversations($this->currentUser()),
                    ],
                    'thread' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->message()))),
                        'args' => ['contactId' => Type::nonNull(Type::int())],
                        'resolve' => fn ($root, $args) => $this->messageResolver->getThread($args, $this->currentUser()),
                    ],

                    // Admin only
                    'adminUsers' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->user()))),
                        'args' => ['page' => Type::int(), 'limit' => Type::int()],
                        'resolve' => fn ($root, $args) => $this->adminResolver->getAllUsers($args, $this->currentUser()),
                    ],
                    'adminProfessionals' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->professional()))),
                        'args' => ['page' => Type::int(), 'limit' => Type::int()],
                        'resolve' => fn ($root, $args) => $this->adminResolver->getAllProfessionals($args, $this->currentUser()),
                    ],
                    'adminAdmins' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->admin()))),
                        'args' => ['page' => Type::int(), 'limit' => Type::int()],
                        'resolve' => fn ($root, $args) => $this->adminResolver->getAllAdmins($args, $this->currentUser()),
                    ],
                    'adminAppointments' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($this->types->appointment()))),
                        'args' => ['page' => Type::int(), 'limit' => Type::int()],
                        'resolve' => fn ($root, $args) => $this->adminResolver->getAllAppointments($args, $this->currentUser()),
                    ],
                    'adminStats' => [
                        'type' => Type::nonNull($this->types->stats()),
                        'resolve' => fn () => $this->adminResolver->getStats($this->currentUser()),
                    ],
                ];
            },
        ]);
    }

    // -----------------------------------------------------------------------
    // Mutation type
    // -----------------------------------------------------------------------

    private function buildMutation(): ObjectType
    {
        $createAdminInput = $this->createAdminInput();
        $updateUserInput = $this->updateUserInput();
        $updateProfessionalInput = $this->updateProfessionalInput();
        $createReviewInput = $this->createReviewInput();
        $serviceInput = $this->serviceInput();
        $updateServiceInput = $this->updateServiceInput();
        $createAppointmentInput = $this->createAppointmentInput();
        $sendMessageInput = $this->sendMessageInput();

        return new ObjectType([
            'name' => 'Mutation',
            'fields' => function () use (
                $updateUserInput, $updateProfessionalInput,
                $createAdminInput, $createReviewInput,
                $serviceInput, $updateServiceInput,
                $createAppointmentInput, $sendMessageInput
            ) {
                return [
                    // Admin creation — login/register happen via REST /auth/* endpoints
                    'createAdmin' => [
                        'type' => Type::nonNull($this->types->authPayload()),
                        'args' => ['input' => Type::nonNull($createAdminInput)],
                        'resolve' => fn ($root, $args) => $this->authResolver->createAdmin($args, $this->currentUser()),
                    ],

                    // User profile
                    'updateUserProfile' => [
                        'type' => Type::nonNull($this->types->user()),
                        'args' => ['input' => Type::nonNull($updateUserInput)],
                        'resolve' => fn ($root, $args) => $this->userResolver->updateProfile($args, $this->currentUser()),
                    ],

                    // Professional profile
                    'updateProfessionalProfile' => [
                        'type' => Type::nonNull($this->types->professional()),
                        'args' => ['input' => Type::nonNull($updateProfessionalInput)],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->updateProfile($args, $this->currentUser()),
                    ],

                    // Services
                    'addService' => [
                        'type' => Type::nonNull($this->types->service()),
                        'args' => ['input' => Type::nonNull($serviceInput)],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->addService($args, $this->currentUser()),
                    ],
                    'updateService' => [
                        'type' => Type::nonNull($this->types->service()),
                        'args' => [
                            'id' => Type::nonNull(Type::int()),
                            'input' => Type::nonNull($updateServiceInput),
                        ],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->updateService($args, $this->currentUser()),
                    ],
                    'deleteService' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'args' => ['id' => Type::nonNull(Type::int())],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->deleteService($args, $this->currentUser()),
                    ],

                    // Reviews
                    'createReview' => [
                        'type' => Type::nonNull($this->types->review()),
                        'args' => ['input' => Type::nonNull($createReviewInput)],
                        'resolve' => fn ($root, $args) => $this->professionalResolver->createReview($args, $this->currentUser()),
                    ],

                    // Appointments
                    'createAppointment' => [
                        'type' => Type::nonNull($this->types->appointment()),
                        'args' => ['input' => Type::nonNull($createAppointmentInput)],
                        'resolve' => fn ($root, $args) => $this->appointmentResolver->createAppointment($args, $this->currentUser()),
                    ],
                    'updateAppointmentStatus' => [
                        'type' => Type::nonNull($this->types->appointment()),
                        'args' => [
                            'id' => Type::nonNull(Type::int()),
                            'status' => Type::nonNull(Type::string()),
                        ],
                        'resolve' => fn ($root, $args) => $this->appointmentResolver->updateAppointmentStatus($args, $this->currentUser()),
                    ],

                    // Messages
                    'sendMessage' => [
                        'type' => Type::nonNull($this->types->message()),
                        'args' => ['input' => Type::nonNull($sendMessageInput)],
                        'resolve' => fn ($root, $args) => $this->messageResolver->sendMessage($args, $this->currentUser()),
                    ],
                    'markMessageRead' => [
                        'type' => Type::nonNull($this->types->message()),
                        'args' => ['id' => Type::nonNull(Type::int())],
                        'resolve' => fn ($root, $args) => $this->messageResolver->markMessageRead($args, $this->currentUser()),
                    ],
                ];
            },
        ]);
    }
}
