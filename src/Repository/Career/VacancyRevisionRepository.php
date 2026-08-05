<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Career\Company;
use App\Entity\Career\VacancyRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

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

    /**
     * How many vacancies are waiting on the committee, for the overviews that only say so rather than list them. Pass
     * a company to count only its own, which is what its page shows.
     */
    public function countForReview(?Company $company = null): int
    {
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status IN (:statuses)')
            ->setParameter(
                'statuses',
                [
                    RevisionStatus::Submitted->value,
                    RevisionStatus::InReview->value,
                ],
            );

        // A vacancy belongs to a company through the package it was posted under, so the count hops over that.
        if (null !== $company) {
            $builder->join(
                'r.vacancy',
                'v',
            )
                ->join(
                    'v.package',
                    'p',
                )
                ->andWhere('p.company = :company')
                ->setParameter(
                    'company',
                    $company->getId(),
                    Types::INTEGER,
                );
        }

        return intval(
            $builder->getQuery()
                ->getSingleScalarResult(),
        );
    }
}
