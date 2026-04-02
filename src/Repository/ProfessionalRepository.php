<?php

namespace App\Repository;

use App\Entity\Professional;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Professional>
 */
class ProfessionalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Professional::class);
    }

    public function findByEmail(string $email): ?Professional
    {
        return $this->findOneBy(['email' => strtolower($email)]);
    }

    public function findByUsername(string $username): ?Professional
    {
        return $this->findOneBy(['username' => strtolower($username)]);
    }

    public function findAllFiltered(?string $language = null, ?string $search = null, ?string $job = null, ?string $location = null, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        // Step 1: Fetch only the paginated IDs.
        // Using a separate query without JOINs avoids the Doctrine LIMIT bug
        // where each joined relation row counts against the LIMIT,
        // causing far fewer root entities than expected to be returned.
        $idQb = $this->createQueryBuilder('p')
            ->select('p.id')
            ->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $this->applyFilters($idQb, $language, $search, $job, $location);

        $ids = array_column($idQb->getQuery()->getArrayResult(), 'id');

        if (empty($ids)) {
            return [];
        }

        // Step 2: Fetch full entities with relations for the found IDs.
        return $this->createQueryBuilder('p')
            ->leftJoin('p.services', 's')
            ->addSelect('s')
            ->leftJoin('p.reviews', 'r')
            ->addSelect('r')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDistinctLanguages(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.languages')
            ->getQuery()
            ->getArrayResult();

        $languages = [];
        foreach ($results as $row) {
            foreach ($row['languages'] as $lang) {
                $languages[$lang] = true;
            }
        }

        return array_keys($languages);
    }

    public function findDistinctJobs(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('DISTINCT p.job')
            ->orderBy('p.job', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($results));
    }

    public function findDistinctLocations(): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('DISTINCT p.location')
            ->where('p.location IS NOT NULL')
            ->orderBy('p.location', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_filter($results));
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFiltered(?string $language = null, ?string $search = null, ?string $job = null, ?string $location = null): int
    {
        if (!$language && !$search && !$job && !$location) {
            return $this->countAll();
        }

        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        $this->applyFilters($qb, $language, $search, $job, $location);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applyFilters(QueryBuilder $qb, ?string $language, ?string $search, ?string $job, ?string $location): void
    {
        if ($search) {
            $qb
                ->andWhere('LOWER(p.firstName) LIKE LOWER(:search) OR LOWER(p.lastName) LIKE LOWER(:search) OR LOWER(p.job) LIKE LOWER(:search) OR LOWER(p.businessName) LIKE LOWER(:search)')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($language) {
            // The languages column is a PostgreSQL JSON array (e.g. ["en","it"]).
            // LIKE on a JSON column requires a cast; we pre-fetch matching IDs via DBAL
            // so the rest of the QueryBuilder stays pure ORM.
            $matchingIds = $this->getEntityManager()->getConnection()
                ->executeQuery(
                    'SELECT id FROM professionals WHERE languages::jsonb @> :lang::jsonb',
                    ['lang' => json_encode([$language])],
                )
                ->fetchFirstColumn();

            if (empty($matchingIds)) {
                $qb->andWhere('1 = 0');
            } else {
                $qb->andWhere('p.id IN (:langIds)')->setParameter('langIds', $matchingIds);
            }
        }

        if ($job) {
            $qb
                ->andWhere('LOWER(p.job) LIKE LOWER(:job)')
                ->setParameter('job', '%'.$job.'%');
        }

        if ($location) {
            $qb
                ->andWhere('LOWER(p.location) LIKE LOWER(:location)')
                ->setParameter('location', '%'.$location.'%');
        }
    }
}
