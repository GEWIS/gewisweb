<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\SignupOption;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignupOption>
 */
class SignupOptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SignupOption::class,
        );
    }
}
