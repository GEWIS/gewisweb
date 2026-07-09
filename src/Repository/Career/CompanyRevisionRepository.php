<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\CompanyRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyRevision>
 */
class CompanyRevisionRepository extends ServiceEntityRepository
{
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
        return $this->createQueryBuilder('r')
            ->addSelect(
                's',
                'c',
            )
            ->join(
                'r.slogan',
                's',
            )
            ->join(
                'r.company',
                'c',
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
