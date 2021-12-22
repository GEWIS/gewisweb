<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Job as JobModel;
use Doctrine\ORM\Query\Expr\Join;

/**
 * Mappers for jobs.
 *
 * NOTE: Jobs will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Job extends BaseMapper
{
    /**
     * Checks if $slugName is only used by object identified with $cid.
     *
     * @param string $companySlugName
     * @param string $jobSlugName
     * @param int $jobCategoryId
     *
     * @return bool
     */
    public function isSlugNameUnique(
        string $companySlugName,
        string $jobSlugName,
        int $jobCategoryId,
    ): bool {
        // A slug in unique if there is no other slug of the same category and same company.
        $jobs = $this->findJob(
            jobCategoryId: $jobCategoryId,
            jobSlugName: $jobSlugName,
            companySlugName: $companySlugName,
        );

        return !(count($jobs) > 0);
    }

    /**
     * Find all jobs identified by $jobSlugName that are owned by a company
     * identified with $companySlugName.
     *
     * @param int|null $jobCategoryId
     * @param string|null $jobCategorySlug
     * @param int|null $jobLabelId
     * @param string|null $jobSlugName
     * @param string|null $companySlugName
     *
     * @return array
     */
    public function findJob(
        int $jobCategoryId = null,
        string $jobCategorySlug = null,
        int $jobLabelId = null,
        string $jobSlugName = null,
        string $companySlugName = null,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->join('j.package', 'p')
            ->addSelect('p')
            ->join('p.company', 'c')
            ->addSelect('c');

        if (null !== $jobCategoryId) {
            $qb->join('j.category', 'cat')
                ->andWhere('cat.id = :jobCategoryId')
                ->setParameter('jobCategoryId', $jobCategoryId);
        }

        if (null !== $jobCategorySlug) {
            $qb->innerJoin('j.category', 'cat')
                ->innerJoin(
                    'cat.slug',
                    'loc',
                    Join::WITH,
                    $qb->expr()->orX(
                        'LOWER(loc.valueEN) = :jobCategorySlug',
                        'LOWER(loc.valueNL) = :jobCategorySlug',
                    )
                )
                ->setParameter('jobCategorySlug', $jobCategorySlug);
        }

        if (null !== $jobLabelId) {
            $qb->join('j.labels', 'l')
                ->andWhere('l.id = :jobLabelId')
                ->setParameter('jobLabelId', $jobLabelId);
        }

        if (null !== $jobSlugName) {
            $qb->andWhere('j.slugName = :jobSlugName')
                ->setParameter('jobSlugName', $jobSlugName);
        }

        if (null !== $companySlugName) {
            $qb->andWhere('c.slugName=:companySlugName')
                ->setParameter('companySlugName', $companySlugName);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobModel::class;
    }
}
