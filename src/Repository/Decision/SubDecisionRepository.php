<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\SubDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubDecision>
 */
class SubDecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SubDecision::class,
        );
    }
}
