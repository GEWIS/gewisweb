<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\OrganInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganInformation>
 */
class OrganInformationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            OrganInformation::class,
        );
    }
}
