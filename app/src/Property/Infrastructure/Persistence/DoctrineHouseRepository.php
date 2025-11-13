<?php

namespace App\Property\Infrastructure\Persistence;

use App\Property\Domain\House;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<House>
 */
class DoctrineHouseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, House::class);
    }

    public function findActiveHouses(): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('h.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOwner(int $ownerId): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.owner = :owner')
            ->andWhere('h.isActive = :active')
            ->setParameter('owner', $ownerId)
            ->setParameter('active', true)
            ->orderBy('h.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}