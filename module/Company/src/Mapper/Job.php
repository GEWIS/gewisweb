<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\Enums\ApprovableStatus;
use Company\Model\Job as JobModel;
use Company\Model\Proposals\JobUpdate as JobUpdateModel;
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
            ->addSelect('c')
            ->where('j.isUpdate = :isUpdate');

        $qb->setParameter('isUpdate', false);

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

    public function findByPackageAndCompany(
        string $companySlugName,
        int $packageId,
        int $jobId,
    ): ?JobModel {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->innerJoin('j.package', 'p', 'WITH', 'p.id = :packageId')
            ->innerJoin('p.company', 'c', 'WITH', 'c.slugName = :companySlugName')
            ->where('j.id = :jobId')
            ->setParameter('jobId', $jobId)
            ->setParameter('packageId', $packageId)
            ->setParameter('companySlugName', $companySlugName);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get the `$count` most recent jobs for a company with a specific status.
     *
     * @return array<array-key, JobModel>
     */
    public function findRecentByApprovedStatus(
        ApprovableStatus $status,
        string $companySlugName,
        int $count = 5,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->innerJoin('j.package', 'p')
            ->innerJoin('p.company', 'c')
            ->where('p.expires > CURRENT_DATE()')
            ->andWhere('j.isUpdate = :isUpdate')
            ->andWhere('j.approved = :status')
            ->andWhere('c.slugName = :slugName')
            ->orderBy('j.id', 'DESC')
            ->setMaxResults($count);

        $qb->setParameter('isUpdate', false)
            ->setParameter('status', $status)
            ->setParameter('slugName', $companySlugName);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<array-key, JobModel>
     */
    public function findProposals(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('j');
        $qb->where('(j.approved = :approved AND j.isUpdate = :isUpdate)');

        $qbu = $this->getEntityManager()->createQueryBuilder();
        $qbu->select('IDENTITY(u.original)')->distinct()
            ->from(JobUpdateModel::class, 'u')
            ->innerJoin('u.proposal', 'p')
            ->where('p.approved = :approved')
            ->orderBy('u.id', 'DESC');

        $qb->orWhere($qb->expr()->in('j.id', $qbu->getDQL()))
            ->orderBy('j.id', 'DESC');

        $qb->setParameter('approved', ApprovableStatus::Unapproved)
            ->setParameter('isUpdate', false);

        return $qb->getQuery()->getResult();
    }

    public function findProposal(int $proposalId): ?JobUpdateModel
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(JobUpdateModel::class, 'u')
            ->where('u.id = :proposalId');

        $qb->setParameter('proposalId', $proposalId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobModel::class;
    }
}
