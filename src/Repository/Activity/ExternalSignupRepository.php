<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ExternalSignup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalSignup>
 */
class ExternalSignupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ExternalSignup::class,
        );
    }
}
