<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\SignupField;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignupField>
 */
class SignupFieldRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SignupField::class,
        );
    }
}
