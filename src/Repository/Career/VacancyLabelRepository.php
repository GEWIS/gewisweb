<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\VacancyLabel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VacancyLabel>
 */
class VacancyLabelRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct(
            $registry,
            VacancyLabel::class,
        );
    }

    /**
     * Every label with its localised name fetch-joined, so rendering the label checkboxes on the overview's filter
     * panel does not lazy-load one name per label.
     *
     * @return VacancyLabel[]
     */
    public function findAllWithName(): array
    {
        return $this->createQueryBuilder('l')
            ->select(
                'l',
                'n',
            )
            ->leftJoin(
                'l.name',
                'n',
            )
            ->orderBy(
                'l.id',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Every label with the number of vacancy revisions carrying it, which is what says whether it may still be
     * removed. Counted in the query: the overview only ever shows the number, and reading it off each label's
     * collection would load every revision of every label to do it.
     *
     * @return list<array{label: VacancyLabel, usage: int}>
     */
    public function findAllWithUsage(): array
    {
        return $this->createQueryBuilder('l')
            ->select(
                'l AS label',
                'COUNT(r.id) AS usage',
            )
            ->leftJoin(
                'l.revisions',
                'r',
            )
            ->groupBy('l.id')
            ->orderBy(
                'l.id',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
