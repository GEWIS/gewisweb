<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevisionComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityRevisionComment>
 */
class ActivityRevisionCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityRevisionComment::class,
        );
    }

    /**
     * The full review discussion across every revision of an activity, oldest first.
     *
     * @return ActivityRevisionComment[]
     */
    public function findThreadForActivity(Activity $activity): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'au',
                'r',
            )
            ->join(
                'c.author',
                'au',
            )
            ->join(
                'c.revision',
                'r',
            )
            ->where('IDENTITY(r.activity) = :activityId')
            ->setParameter(
                'activityId',
                $activity->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'c.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
