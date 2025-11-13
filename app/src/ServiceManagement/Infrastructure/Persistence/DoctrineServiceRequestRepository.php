<?php

namespace App\ServiceManagement\Infrastructure\Persistence;

use App\ServiceManagement\Domain\ServiceRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceRequest>
 */
class DoctrineServiceRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceRequest::class);
    }

    public function findByState(string $state): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.currentPlace = :state')
            ->setParameter('state', $state)
            ->orderBy('sr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByAssignedCleaner(int $cleanerId): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.assignedCleaner = :cleaner')
            ->setParameter('cleaner', $cleanerId)
            ->orderBy('sr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByHouse(int $houseId): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.house = :house')
            ->setParameter('house', $houseId)
            ->orderBy('sr.scheduledDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcoming(\DateTimeImmutable $fromDate = null): array
    {
        $qb = $this->createQueryBuilder('sr')
            ->andWhere('sr.scheduledDate >= :fromDate')
            ->andWhere('sr.currentPlace NOT IN (:excludedStates)')
            ->setParameter('fromDate', $fromDate ?? new \DateTimeImmutable())
            ->setParameter('excludedStates', ['completed', 'cancelled', 'archived'])
            ->orderBy('sr.scheduledDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findAvailableForSelfAssignment(): array
    {
        return $this->createQueryBuilder('sr')
            ->andWhere('sr.currentPlace = :state')
            ->andWhere('sr.assignedCleaner IS NULL')
            ->setParameter('state', 'scheduled')
            ->orderBy('sr.scheduledDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}