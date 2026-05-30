<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\SignupList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignupList>
 */
class SignupListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SignupList::class,
        );
    }
}
