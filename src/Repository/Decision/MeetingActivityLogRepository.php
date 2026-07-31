<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\MeetingActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MeetingActivityLog>
 */
class MeetingActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MeetingActivityLog::class,
        );
    }
}
