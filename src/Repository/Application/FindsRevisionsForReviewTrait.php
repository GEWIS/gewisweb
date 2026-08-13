<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\Enums\RevisionStatus;
use Doctrine\ORM\QueryBuilder;

use function intval;

/**
 * What "waiting for a reviewer" means, in one place: submitted, or already being looked at. Every revisable domain has
 * a queue built on it, and which statuses belong in one is a property of the workflow rather than of any single domain.
 *
 * A repository still writes its own selects and joins, because what a queue row needs in order to name its subject
 * differs per domain.
 */
trait FindsRevisionsForReviewTrait
{
    /**
     * Narrow $builder to the revisions waiting for a reviewer.
     */
    protected function whereAwaitingReview(
        QueryBuilder $builder,
        string $alias = 'r',
    ): QueryBuilder {
        return $builder->andWhere($alias . '.status IN (:awaitingReviewStatuses)')
            ->setParameter(
                'awaitingReviewStatuses',
                [
                    RevisionStatus::Submitted->value,
                    RevisionStatus::InReview->value,
                ],
            );
    }

    /**
     * How many are waiting on a reviewer, for the overviews that only say so rather than list them.
     */
    public function countForReview(): int
    {
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');

        $this->whereAwaitingReview($builder);

        return intval(
            $builder->getQuery()
                ->getSingleScalarResult(),
        );
    }

    /**
     * Longest waiting first, so nothing sits in a queue unanswered while later submissions are dealt with. By when it
     * was submitted rather than by when it was written: a draft somebody spent a week on is not older than one
     * submitted the day it was started.
     */
    protected function orderOldestFirst(
        QueryBuilder $builder,
        string $alias = 'r',
    ): QueryBuilder {
        return $builder->orderBy(
            $alias . '.submittedAt',
            'ASC',
        );
    }
}
