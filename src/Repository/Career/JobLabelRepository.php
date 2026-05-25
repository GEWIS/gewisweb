<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\JobLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobLabel>
 */
class JobLabelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            JobLabel::class,
        );
    }
}
