<?php

namespace App\ServiceManagement\Infrastructure\Persistence;

use App\ServiceManagement\Domain\ServiceTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceTask>
 */
class DoctrineServiceTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceTask::class);
    }
}