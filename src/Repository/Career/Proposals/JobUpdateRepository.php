<?php

declare(strict_types=1);

namespace App\Repository\Career\Proposals;

use App\Entity\Career\Proposals\JobUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobUpdate>
 */
class JobUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            JobUpdate::class,
        );
    }
}
