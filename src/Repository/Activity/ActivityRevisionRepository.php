<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityRevision>
 */
class ActivityRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActivityRevision::class,
        );
    }

    /**
     * The revisions awaiting board attention (submitted, or already being reviewed), oldest first.
     *
     * @return ActivityRevision[]
     */
    public function findForReview(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect(
                'n',
                'a',
            )
            ->join(
                'r.name',
                'n',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('r.status IN (:statuses)')
            ->setParameter(
                'statuses',
                [
                    RevisionStatus::Submitted->value,
                    RevisionStatus::InReview->value,
                ],
            )
            ->orderBy(
                'r.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
