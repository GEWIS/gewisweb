<?php

declare(strict_types=1);

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\Enums\ApprovableStatus;
use Company\Model\Company as CompanyModel;
use Company\Model\Proposals\CompanyUpdate as CompanyUpdateModel;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Override;

/**
 * Mappers for companies.
 *
 * @template-extends BaseMapper<CompanyModel>
 */
class Company extends BaseMapper
{
    /**
     * Find all public companies, these are companies that are published and have at least one non-expired package.
     *
     * @return CompanyModel[]
     */
    public function findAllPublic(): array
    {
        $rsmBuilder = new ResultSetMappingBuilder($this->getEntityManager());
        $rsmBuilder->addRootEntityFromClassMetadata($this->getRepositoryName(), 'c');

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

        return $this->getEntityManager()->createNativeQuery($sql, $rsmBuilder)->getResult();
    }

    /**
     * Return the company with the given slug.
     *
     * @param string $slugName the slugname to find
     */
    public function findCompanyBySlugName(string $slugName): ?CompanyModel
    {
        return $this->getRepository()->findOneBy(['slugName' => $slugName]);
    }

    /**
     * Return a company by a given representative's e-mail address.
     */
    public function findCompanyByRepresentativeEmail(string $email): ?CompanyModel
    {
        return $this->getRepository()->findOneBy(['representativeEmail' => $email]);
    }

    /**
     * @return CompanyModel[]
     */
    public function findUpdateProposals(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('c');
        $qb->where('(c.approved = :approved AND c.isUpdate = :isUpdate)');

        $qbu = $this->getEntityManager()->createQueryBuilder();
        $qbu->select('IDENTITY(u.original)')->distinct()
            ->from(CompanyUpdateModel::class, 'u')
            ->orderBy('u.id', 'DESC');

        $qb->orWhere($qb->expr()->in('c.id', $qbu->getDQL()))
            ->orderBy('c.id', 'DESC');

        $qb->setParameter('approved', ApprovableStatus::Unapproved)
            ->setParameter('isUpdate', false);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all companies that were approved or rejected by a specific member.
     *
     * @return CompanyModel[]
     */
    public function findAllCompaniesApprovedByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('c');
        $qb->where('c.approver = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return CompanyModel::class;
    }
}
