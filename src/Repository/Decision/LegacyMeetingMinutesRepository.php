<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\LegacyMeetingMinutes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Only used by the one-shot legacy document migrator; dropped together with the entity.
 *
 * @internal
 *
 * @extends ServiceEntityRepository<LegacyMeetingMinutes>
 */
class LegacyMeetingMinutesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            LegacyMeetingMinutes::class,
        );
    }
}
