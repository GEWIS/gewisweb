<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\ApprovableStatus;
use App\Entity\Career\Job;
use App\Entity\Career\Proposals\JobUpdate;
use App\Entity\Decision\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

use function count;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Job::class,
        );
    }

    /**
     * Checks if $slugName is only used by object identified with $cid.
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
     * @return Job[]
     */
    public function findJob(
        ?int $jobCategoryId = null,
        ?string $jobCategorySlug = null,
        ?int $jobLabelId = null,
        ?string $jobSlugName = null,
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
            ->where('j.isUpdate = :isUpdate');

        $qb->setParameter(
            'isUpdate',
            false,
        );

        if (null !== $jobCategoryId) {
            $qb->join(
                'j.category',
                'cat',
            )
                ->andWhere('cat.id = :jobCategoryId')
                ->setParameter(
                    'jobCategoryId',
                    $jobCategoryId,
                );
        } elseif (null !== $jobCategorySlug) {
            $qb->innerJoin(
                'j.category',
                'cat',
            )
                ->innerJoin(
                    'cat.slug',
                    'loc',
                    Join::WITH,
                    $qb->expr()->orX(
                        'LOWER(loc.valueEN) = :jobCategorySlug',
                        'LOWER(loc.valueNL) = :jobCategorySlug',
                    ),
                )
                ->setParameter(
                    'jobCategorySlug',
                    $jobCategorySlug,
                );
        }

        if (null !== $jobLabelId) {
            $qb->join(
                'j.labels',
                'l',
            )
                ->andWhere('l.id = :jobLabelId')
                ->setParameter(
                    'jobLabelId',
                    $jobLabelId,
                );
        }

        if (null !== $jobSlugName) {
            $qb->andWhere('j.slugName = :jobSlugName')
                ->setParameter(
                    'jobSlugName',
                    $jobSlugName,
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
        int $jobId,
    ): ?Job {
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
            ->where('j.id = :jobId')
            ->setParameter(
                'jobId',
                $jobId,
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

    /**
     * Get the `$count` most recent jobs for a company with a specific status.
     *
     * @return Job[]
     */
    public function findRecentByApprovedStatus(
        ApprovableStatus $status,
        string $companySlugName,
        int $count = 5,
    ): array {
        $qb = $this->createQueryBuilder('j');
        $qb->innerJoin(
            'j.package',
            'p',
        )
            ->innerJoin(
                'p.company',
                'c',
            )
            ->where('p.expires > CURRENT_DATE()')
            ->andWhere('j.isUpdate = :isUpdate')
            ->andWhere('j.approved = :status')
            ->andWhere('c.slugName = :slugName')
            ->orderBy(
                'j.id',
                'DESC',
            )
            ->setMaxResults($count);

        $qb->setParameter(
            'isUpdate',
            false,
        )
            ->setParameter(
                'status',
                $status,
                ApprovableStatus::class,
            )
            ->setParameter(
                'slugName',
                $companySlugName,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Job[]
     */
    public function findProposals(): array
    {
        $qb = $this->createQueryBuilder('j');
        $qb->where('(j.approved = :approved AND j.isUpdate = :isUpdate)');

        $qbu = $this->getEntityManager()->createQueryBuilder();
        $qbu->select('IDENTITY(u.original)')->distinct()
            ->from(
                JobUpdate::class,
                'u',
            )
            ->innerJoin(
                'u.proposal',
                'p',
            )
            ->where('p.approved = :approved')
            ->orderBy(
                'u.id',
                'DESC',
            );

        $qb->orWhere($qb->expr()->in('j.id', $qbu->getDQL()))
            ->orderBy(
                'j.id',
                'DESC',
            );

        $qb->setParameter(
            'approved',
            ApprovableStatus::Unapproved,
        )
            ->setParameter(
                'isUpdate',
                false,
            );

        return $qb->getQuery()->getResult();
    }

    public function findProposal(int $proposalId): ?JobUpdate
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from(
                JobUpdate::class,
                'u',
            )
            ->where('u.id = :proposalId');

        $qb->setParameter(
            'proposalId',
            $proposalId,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all jobs that were approved or rejected by a specific member.
     *
     * @return Job[]
     */
    public function findAllJobsApprovedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('j');
        $qb->where('j.approver = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }
}
