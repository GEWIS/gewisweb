<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Vacancy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vacancy>
 */
class VacancyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Vacancy::class,
        );
    }

    /**
     * Checks whether $vacancySlugName is still free within the given company and category.
     *
     * A slug is unique when no vacancy of the same company and category already uses it. This deliberately does NOT
     * route through {@see self::findVacancy()} (whose `liveRevision` inner join would hide not-yet-approved vacancies
     * and let a pending vacancy collide unseen); it matches on the stable slug columns and resolves the category off
     * the working head ({@see Vacancy::getCurrentRevision()}), where the category now lives.
     */
    public function isSlugNameUnique(
        string $companySlugName,
        string $vacancySlugName,
        int $vacancyCategoryId,
    ): bool {
        $count = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->join(
                'v.package',
                'p',
            )
            ->join(
                'p.company',
                'c',
            )
            ->join(
                'v.currentRevision',
                'cr',
            )
            ->where('c.slugName = :companySlugName')
            ->andWhere('v.slugName = :vacancySlugName')
            ->andWhere('IDENTITY(cr.category) = :vacancyCategoryId')
            ->setParameter(
                'companySlugName',
                $companySlugName,
            )
            ->setParameter(
                'vacancySlugName',
                $vacancySlugName,
            )
            ->setParameter(
                'vacancyCategoryId',
                $vacancyCategoryId,
            )
            ->getQuery()
            ->getSingleScalarResult();

        return 0 === (int) $count;
    }

    /**
     * Find all vacancies identified by $vacancySlugName that are owned by a company
     * identified with $companySlugName.
     *
     * The category lives on the live (approved) revision, so category filtering joins through it.
     *
     * @return Vacancy[]
     */
    public function findVacancy(
        ?int $vacancyCategoryId = null,
        ?string $vacancyCategorySlug = null,
        ?int $vacancyLabelId = null,
        ?string $vacancySlugName = null,
        ?string $companySlugName = null,
    ): array {
        $qb = $this->createQueryBuilder('j');
        $qb->join(
            'j.package',
            'p',
        )
            ->addSelect('p')
            ->join(
                'p.company',
                'c',
            )
            ->addSelect('c')
            ->join(
                'j.liveRevision',
                'lr',
            )
            ->addSelect('lr');

        if (null !== $vacancyCategoryId) {
            $qb->join(
                'lr.category',
                'cat',
            )
                ->andWhere('cat.id = :vacancyCategoryId')
                ->setParameter(
                    'vacancyCategoryId',
                    $vacancyCategoryId,
                );
        } elseif (null !== $vacancyCategorySlug) {
            $qb->innerJoin(
                'lr.category',
                'cat',
            )
                ->innerJoin(
                    'cat.slug',
                    'loc',
                    Join::WITH,
                    $qb->expr()->orX(
                        'LOWER(loc.valueEN) = :vacancyCategorySlug',
                        'LOWER(loc.valueNL) = :vacancyCategorySlug',
                    ),
                )
                ->setParameter(
                    'vacancyCategorySlug',
                    $vacancyCategorySlug,
                );
        }

        if (null !== $vacancyLabelId) {
            $qb->join(
                'j.labels',
                'l',
            )
                ->andWhere('l.id = :vacancyLabelId')
                ->setParameter(
                    'vacancyLabelId',
                    $vacancyLabelId,
                );
        }

        if (null !== $vacancySlugName) {
            $qb->andWhere('j.slugName = :vacancySlugName')
                ->setParameter(
                    'vacancySlugName',
                    $vacancySlugName,
                );
        }

        if (null !== $companySlugName) {
            $qb->andWhere('c.slugName=:companySlugName')
                ->setParameter(
                    'companySlugName',
                    $companySlugName,
                );
        }

        return $qb->getQuery()->getResult();
    }

    public function findByPackageAndCompany(
        string $companySlugName,
        int $packageId,
        int $vacancyId,
    ): ?Vacancy {
        $qb = $this->createQueryBuilder('j');
        $qb->innerJoin(
            'j.package',
            'p',
            'WITH',
            'p.id = :packageId',
        )
            ->innerJoin(
                'p.company',
                'c',
                'WITH',
                'c.slugName = :companySlugName',
            )
            ->where('j.id = :vacancyId')
            ->setParameter(
                'vacancyId',
                $vacancyId,
            )
            ->setParameter(
                'packageId',
                $packageId,
            )
            ->setParameter(
                'companySlugName',
                $companySlugName,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }
}
