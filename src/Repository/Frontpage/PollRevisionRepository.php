<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\Member;
use App\Entity\Frontpage\PollRevision;
use App\Repository\Application\FindsRevisionsForReviewTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PollRevision>
 */
class PollRevisionRepository extends ServiceEntityRepository
{
    use FindsRevisionsForReviewTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollRevision::class,
        );
    }

    /**
     * The questions waiting on the board, oldest first. A queue row names the poll by its question, so that comes
     * along with it.
     *
     * @return PollRevision[]
     */
    public function findForReview(): array
    {
        $builder = $this->createQueryBuilder('r')
            ->addSelect(
                'p',
                'q',
                'a',
            )
            ->join(
                'r.poll',
                'p',
            )
            ->join(
                'r.question',
                'q',
            )
            ->leftJoin(
                'r.author',
                'a',
            );

        $this->whereAwaitingReview($builder);
        $this->orderOldestFirst($builder);

        return $builder->getQuery()
            ->getResult();
    }

    /**
     * The questions this member decided on, for their data export. Reviewing is something the member did, so it is
     * theirs to be told about regardless of what the decision was.
     *
     * @return PollRevision[]
     */
    public function findReviewedByMember(Member $member): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reviewer = :member')
            ->andWhere('r.status IN (:decidedStatuses)')
            ->setParameter(
                'member',
                $member->getLidnr(),
            )
            ->setParameter(
                'decidedStatuses',
                [
                    RevisionStatus::Approved->value,
                    RevisionStatus::Rejected->value,
                    RevisionStatus::Closed->value,
                ],
            )
            ->orderBy(
                'r.reviewedAt',
                'DESC',
            )
            ->getQuery()
            ->getResult();
    }
}
