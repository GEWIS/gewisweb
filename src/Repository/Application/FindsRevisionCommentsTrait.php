<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\AbstractRevisionComment;
use App\Entity\Application\RevisionInterface;
use Doctrine\DBAL\Types\Types;
use Override;

/**
 * Reading a review discussion, which is the same query in every domain: the `revision` association is mapped on each
 * concrete comment class, but it is always called `revision`, and a thread across a whole chain only differs in which
 * association on the revision points back at the aggregate.
 *
 * A repository using this still answers {@see RevisionCommentRepositoryInterface::supports()} for itself, since which
 * revisions are its own is the one thing that genuinely differs.
 */
trait FindsRevisionCommentsTrait
{
    /**
     * The association on this domain's revision that points back at the aggregate a thread belongs to.
     */
    abstract protected function revisionAggregateField(): string;

    /**
     * The full review discussion across every revision of one aggregate, oldest first.
     *
     * @return list<AbstractRevisionComment>
     */
    protected function findThread(?int $aggregateId): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'au',
                'acu',
                'r',
            )
            // A comment's author is a member's account OR a company user (mutually exclusive, both nullable), so both
            // must be LEFT-joined: an inner join on c.author alone silently drops every CompanyUser-authored comment.
            ->leftJoin(
                'c.author',
                'au',
            )
            ->leftJoin(
                'c.authorCompanyUser',
                'acu',
            )
            ->join(
                'c.revision',
                'r',
            )
            // The field name comes from the repository itself, never from a request.
            ->where('IDENTITY(r.' . $this->revisionAggregateField() . ') = :aggregateId')
            ->setParameter(
                'aggregateId',
                $aggregateId,
                Types::INTEGER,
            )
            ->orderBy(
                'c.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<AbstractRevisionComment>
     */
    #[Override]
    public function findForRevision(RevisionInterface $revision): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.revision = :revision')
            ->setParameter(
                'revision',
                $revision->getId(),
                Types::INTEGER,
            )
            ->getQuery()
            ->getResult();
    }
}
