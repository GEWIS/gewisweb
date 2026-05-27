<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MeetingMinutes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingMinutes>
 */
class MeetingMinutesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingMinutes::class,
        );
    }
}
