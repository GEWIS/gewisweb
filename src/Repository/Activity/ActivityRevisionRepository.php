<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\RevisionStatus;
use App\Repository\Application\FindsRevisionsForReviewTrait;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityRevision>
 */
class ActivityRevisionRepository extends ServiceEntityRepository
{
    use FindsRevisionsForReviewTrait;

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
        // The queue says who put each one forward and what is live while it waits, so both come along with it.
        $builder = $this->createQueryBuilder('r')
            ->addSelect(
                'n',
                'a',
                'au',
                'lr',
            )
            ->join(
                'r.name',
                'n',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->leftJoin(
                'r.author',
                'au',
            )
            ->leftJoin(
                'a.liveRevision',
                'lr',
            );

        $this->whereAwaitingReview($builder);
        $this->orderOldestFirst($builder);

        return $builder->getQuery()
            ->getResult();
    }

    /**
     * Draft revisions that are still the working head of their activity and have not been touched since the cutoff,
     * oldest first. These are abandoned drafts eligible for cleanup; submitted/in-review revisions (with the board)
     * are never returned.
     *
     * @return ActivityRevision[]
     */
    public function findStaleDraftHeads(DateTime $cutoff): array
    {
        return $this->createQueryBuilder('r')
            ->join(
                'r.activity',
                'a',
            )
            ->where('r.status = :draft')
            ->andWhere('r.updatedAt <= :cutoff')
            ->andWhere('a.currentRevision = r')
            ->setParameter(
                'draft',
                RevisionStatus::Draft->value,
            )
            ->setParameter(
                'cutoff',
                $cutoff,
                Types::DATETIME_MUTABLE,
            )
            ->orderBy(
                'r.updatedAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
