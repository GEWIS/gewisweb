<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MeetingPoint;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingPoint>
 */
class MeetingPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingPoint::class,
        );
    }
}
