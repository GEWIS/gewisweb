<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollRevision;
use App\Entity\Frontpage\PollRevisionComment;
use App\Repository\Application\FindsRevisionCommentsTrait;
use App\Repository\Application\RevisionCommentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Override;

/**
 * @extends ServiceEntityRepository<PollRevisionComment>
 */
class PollRevisionCommentRepository extends ServiceEntityRepository implements RevisionCommentRepositoryInterface
{
    use FindsRevisionCommentsTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollRevisionComment::class,
        );
    }

    #[Override]
    public function supports(RevisionInterface $revision): bool
    {
        return $revision instanceof PollRevision;
    }

    /**
     * The whole review discussion across every revision of a question, oldest first.
     *
     * @return list<AbstractRevisionComment>
     */
    public function findThreadForPoll(Poll $poll): array
    {
        return $this->findThread($poll->getId());
    }

    #[Override]
    protected function revisionAggregateField(): string
    {
        return 'poll';
    }
}
