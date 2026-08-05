<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\ActivityRevision;
use App\Entity\Activity\ActivityRevisionComment;
use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Repository\Application\FindsRevisionCommentsTrait;
use App\Repository\Application\RevisionCommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<ActivityRevisionComment>
 */
class ActivityRevisionCommentRepository extends ServiceEntityRepository implements RevisionCommentRepositoryInterface
{
    use FindsRevisionCommentsTrait;

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof ActivityRevision;
    }

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
     * @return list<AbstractRevisionComment>
     */
    public function findThreadForActivity(Activity $activity): array
    {
        return $this->findThread($activity->getId());
    }

    #[Override]
    protected function revisionAggregateField(): string
    {
        return 'activity';
    }
}
