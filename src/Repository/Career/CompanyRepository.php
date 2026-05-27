<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Application\Enums\ApprovableStatus;
use App\Entity\Career\Company;
use App\Entity\Career\Proposals\CompanyUpdate;
use App\Entity\Decision\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Company::class,
        );
    }

    /**
     * Find all public companies, these are companies that are published and have at least one non-expired package.
     *
     * @return Company[]
     */
    public function findAllPublic(): array
    {
        $rsmBuilder = new ResultSetMappingBuilder($this->getEntityManager());
        $rsmBuilder->addRootEntityFromClassMetadata(
            $this->getEntityName(),
            'c',
        );

        $select = $rsmBuilder->generateSelectClause(['c' => 't1']);
        $approved = ApprovableStatus::Approved->value;

        $sql = <<<QUERY
            SELECT {$select} FROM `Company` AS `t1`
            LEFT JOIN (
                SELECT `company_id`,
                    COUNT(`company_id`) AS `totalPackages`,
                    SUM(
                        CASE WHEN `expires` <= CURRENT_TIMESTAMP
                                OR `published` = 0
                                OR `starts` > CURRENT_TIMESTAMP
                            THEN 1
                            ELSE 0
                        END
                    ) AS `expiredHiddenOrNotStartedPackages`
                FROM `CompanyPackage`
                GROUP BY `company_id`
            ) `CompanyPackages` ON `CompanyPackages`.`company_id` = `t1`.`id`
            WHERE `t1`.`published` = 1
            AND `t1`.`approved` = "{$approved}"
            AND `CompanyPackages`.`totalPackages` > `CompanyPackages`.`expiredHiddenOrNotStartedPackages`
            ORDER BY `t1`.`name` ASC
            QUERY;

        return $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsmBuilder,
        )->getResult();
    }

    /**
     * Return the company with the given slug.
     *
     * @param string $slugName the slugname to find
     */
    public function findCompanyBySlugName(string $slugName): ?Company
    {
        return $this->findOneBy(['slugName' => $slugName]);
    }

    /**
     * Return a company by a given representative's email address.
     */
    public function findCompanyByRepresentativeEmail(string $email): ?Company
    {
        return $this->findOneBy(['representativeEmail' => $email]);
    }

    /**
     * @return Company[]
     */
    public function findUpdateProposals(): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('(c.approved = :approved AND c.isUpdate = :isUpdate)');

        $qbu = $this->getEntityManager()->createQueryBuilder();
        $qbu->select('IDENTITY(u.original)')->distinct()
            ->from(
                CompanyUpdate::class,
                'u',
            )
            ->orderBy(
                'u.id',
                'DESC',
            );

        $qb->orWhere($qb->expr()->in('c.id', $qbu->getDQL()))
            ->orderBy(
                'c.id',
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

    /**
     * Get all companies that were approved or rejected by a specific member.
     *
     * @return Company[]
     */
    public function findAllCompaniesApprovedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.approver = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }
}
