<?php

namespace App\GraphQL\Resolver;

use App\Entity\Admin;
use App\Entity\Professional;
use App\Entity\Review;
use App\Entity\Service;
use App\Entity\User;
use App\Repository\ProfessionalRepository;
use App\Repository\ReviewRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Error\UserError;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class ProfessionalResolver
{
    public function __construct(
        private readonly ProfessionalRepository $professionalRepo,
        private readonly ServiceRepository $serviceRepo,
        private readonly ReviewRepository $reviewRepo,
        private readonly EntityManagerInterface $em,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getProfessionals(array $args): array
    {
        $language = $args['language'] ?? null;
        $search   = $args['search'] ?? null;
        $job      = $args['job'] ?? null;
        $location = $args['location'] ?? null;
        $page     = max(1, (int) ($args['page'] ?? 1));
        $limit    = min(50, max(1, (int) ($args['limit'] ?? 20)));

        $hasFilter = $language || $search || $job || $location;

        if (!$hasFilter) {
            $cacheKey = sprintf('professionals_p%d_l%d', $page, $limit);

            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($page, $limit) {
                $item->expiresAfter(300);

                return [
                    'items' => $this->professionalRepo->findAllFiltered(null, null, null, null, $page, $limit),
                    'total' => $this->professionalRepo->countAll(),
                ];
            });
        }

        return [
            'items' => $this->professionalRepo->findAllFiltered($language, $search, $job, $location, $page, $limit),
            'total' => $this->professionalRepo->countFiltered($language, $search, $job, $location),
        ];
    }

    public function getProfessional(array $args): Professional
    {
        $professional = $this->professionalRepo->find($args['id']);
        if (!$professional) {
            throw new UserError('Professional not found.');
        }

        return $professional;
    }

    public function getLanguages(): array
    {
        return $this->cache->get('professionals_languages', function (ItemInterface $item) {
            $item->expiresAfter(600);

            return $this->professionalRepo->findDistinctLanguages();
        });
    }

    public function getJobs(): array
    {
        return $this->cache->get('professionals_jobs', function (ItemInterface $item) {
            $item->expiresAfter(600);

            return $this->professionalRepo->findDistinctJobs();
        });
    }

    public function getLocations(): array
    {
        return $this->cache->get('professionals_locations', function (ItemInterface $item) {
            $item->expiresAfter(600);

            return $this->professionalRepo->findDistinctLocations();
        });
    }

    public function getMyProfile(User|Professional|Admin|null $currentUser): Professional
    {
        if (!$currentUser instanceof Professional) {
            throw new UserError('Authentication required. Must be a professional.');
        }

        return $currentUser;
    }

    public function updateProfile(array $args, User|Professional|Admin|null $currentUser): Professional
    {
        if (!$currentUser instanceof Professional) {
            throw new UserError('Authentication required. Must be a professional.');
        }

        $input = $args['input'];
        if (isset($input['firstName'])) {
            $currentUser->setFirstName($input['firstName']);
        }
        if (isset($input['lastName'])) {
            $currentUser->setLastName($input['lastName']);
        }
        if (isset($input['businessName'])) {
            $currentUser->setBusinessName($input['businessName']);
        }
        if (isset($input['job'])) {
            $currentUser->setJob($input['job']);
        }
        if (isset($input['description'])) {
            $currentUser->setDescription($input['description']);
        }
        if (isset($input['phone'])) {
            $currentUser->setPhone($input['phone']);
        }
        if (isset($input['languages'])) {
            $currentUser->setLanguages($input['languages']);
        }
        if (isset($input['location'])) {
            $currentUser->setLocation($input['location']);
        }
        if (isset($input['yearsOfExperience'])) {
            $currentUser->setYearsOfExperience((int) $input['yearsOfExperience']);
        }
        if (isset($input['videoUrl'])) {
            $currentUser->setVideoUrl($input['videoUrl']);
        }
        if (isset($input['degrees'])) {
            $currentUser->setDegrees($input['degrees']);
        }
        if (isset($input['areasOfExpertise'])) {
            $currentUser->setAreasOfExpertise($input['areasOfExpertise']);
        }
        if (isset($input['whoIWorkWith'])) {
            $currentUser->setWhoIWorkWith($input['whoIWorkWith']);
        }
        if (isset($input['specialities'])) {
            $currentUser->setSpecialities($input['specialities']);
        }

        $this->em->flush();
        $this->cache->delete('professionals_listing_p1');
        $this->cache->delete('professionals_languages');
        $this->cache->delete('professionals_jobs');
        $this->cache->delete('professionals_locations');

        return $currentUser;
    }

    public function createReview(array $args, User|Professional|Admin|null $currentUser): Review
    {
        if (!$currentUser instanceof User) {
            throw new UserError('Only users can leave reviews.');
        }

        $input         = $args['input'];
        $appointmentId = (int) $input['appointmentId'];
        $rating        = (int) $input['rating'];

        if ($rating < 1 || $rating > 5) {
            throw new UserError('Rating must be between 1 and 5.');
        }

        $appointment = $this->reviewRepo->findCompletedAppointmentForReview($currentUser, $appointmentId);

        if (!$appointment) {
            throw new UserError('You can only review a professional after a completed appointment.');
        }

        if ($appointment->getReview() !== null) {
            throw new UserError('You have already submitted a review for this appointment.');
        }

        $review = new Review();
        $review->setProfessional($appointment->getProfessional());
        $review->setUser($currentUser);
        $review->setRating($rating);
        $review->setComment($input['comment'] ?? null);
        $review->setAppointment($appointment);

        $this->em->persist($review);
        $this->em->flush();

        $this->cache->delete('professionals_listing_p1');

        return $review;
    }

    public function addService(array $args, User|Professional|Admin|null $currentUser): Service
    {
        if (!$currentUser instanceof Professional) {
            throw new UserError('Authentication required. Must be a professional.');
        }

        $input = $args['input'];
        $service = new Service();
        $service->setProfessional($currentUser);
        $service->setName($input['name']);
        $service->setDescription($input['description'] ?? null);
        $service->setCost((float) $input['cost']);
        $service->setDuration($input['duration'] ?? null);
        $service->setLanguages($input['languages'] ?? []);

        $this->em->persist($service);
        $this->em->flush();

        return $service;
    }

    public function updateService(array $args, User|Professional|Admin|null $currentUser): Service
    {
        if (!$currentUser instanceof Professional) {
            throw new UserError('Authentication required. Must be a professional.');
        }

        $service = $this->serviceRepo->find($args['id']);
        if (!$service || $service->getProfessional()->getId() !== $currentUser->getId()) {
            throw new UserError('Service not found or access denied.');
        }

        $input = $args['input'];
        if (isset($input['name'])) {
            $service->setName($input['name']);
        }
        if (isset($input['description'])) {
            $service->setDescription($input['description']);
        }
        if (isset($input['cost'])) {
            $service->setCost((float) $input['cost']);
        }
        if (isset($input['duration'])) {
            $service->setDuration($input['duration']);
        }
        if (isset($input['languages'])) {
            $service->setLanguages($input['languages']);
        }

        $this->em->flush();

        return $service;
    }

    public function deleteService(array $args, User|Professional|Admin|null $currentUser): bool
    {
        if (!$currentUser instanceof Professional) {
            throw new UserError('Authentication required. Must be a professional.');
        }

        $service = $this->serviceRepo->find($args['id']);
        if (!$service || $service->getProfessional()->getId() !== $currentUser->getId()) {
            throw new UserError('Service not found or access denied.');
        }

        $this->em->remove($service);
        $this->em->flush();

        return true;
    }
}
