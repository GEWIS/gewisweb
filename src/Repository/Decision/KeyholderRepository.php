<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Keyholder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Keyholder>
 */
class KeyholderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Keyholder::class,
        );
    }
}
