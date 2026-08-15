<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\CompanyRevision;
use App\Repository\Application\FindsRevisionsForReviewTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyRevision>
 */
class CompanyRevisionRepository extends ServiceEntityRepository
{
    use FindsRevisionsForReviewTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyRevision::class,
        );
    }

    /**
     * The revisions awaiting board attention (submitted, or already being reviewed), oldest first.
     *
     * @return CompanyRevision[]
     */
    public function findForReview(): array
    {
        // The queue says who put each one forward, which is either a member or a representative, and what is live
        // while it waits.
        $builder = $this->createQueryBuilder('r')
            ->addSelect(
                's',
                'c',
                'a',
                'acu',
                'lr',
            )
            ->join(
                'r.slogan',
                's',
            )
            ->join(
                'r.company',
                'c',
            )
            ->leftJoin(
                'r.author',
                'a',
            )
            ->leftJoin(
                'r.authorCompanyUser',
                'acu',
            )
            ->leftJoin(
                'c.liveRevision',
                'lr',
            );

        $this->whereAwaitingReview($builder);
        $this->orderOldestFirst($builder);

        return $builder->getQuery()
            ->getResult();
    }
}
