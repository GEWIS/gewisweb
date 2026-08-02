<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\VacancyRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancyRevision>
 */
class VacancyRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            VacancyRevision::class,
        );
    }

    /**
     * The revisions awaiting board attention (submitted, or already being reviewed), oldest first.
     *
     * @return VacancyRevision[]
     */
    public function findForReview(): array
    {
        // The queue says who put each one forward, which is either a member or a representative.
        return $this->createQueryBuilder('r')
            ->addSelect(
                'n',
                'j',
                'a',
                'acu',
            )
            ->join(
                'r.name',
                'n',
            )
            ->join(
                'r.vacancy',
                'j',
            )
            ->leftJoin(
                'r.author',
                'a',
            )
            ->leftJoin(
                'r.authorCompanyUser',
                'acu',
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
