<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Decision\Model\OrganMember as OrganMemberModel;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

use function strtolower;

/**
 * @template-extends BaseMapper<MemberModel>
 */
class Member extends BaseMapper
{
    /**
     * Find a member by its membership number (NOTE: only members who are not deleted are returned).
     *
     * @param int $number Membership number
     */
    public function findByLidnr(int $number): ?MemberModel
    {
        return $this->getRepository()->findOneBy([
            'lidnr' => $number,
            'deleted' => false,
        ]);
    }

    /**
     * Finds members (lidnr, full name, and generation) by (part of) their name.
     *
     * @param string $name (part of) the full name of a member
     *
     * @return array<array-key, array{
     *     lidnr: int,
     *     fullName: string,
     *     generation: int,
     * }>
     */
    public function searchByName(
        string $name,
        int $maxResults = 32,
        string $orderColumn = 'generation',
        string $orderDirection = 'DESC',
    ): array {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('lidnr', 'lidnr', 'integer')
            ->addScalarResult('fullName', 'fullName')
            ->addScalarResult('generation', 'generation', 'integer');

        $sql = <<<QUERY
            SELECT `lidnr`,
                CONCAT_WS(' ', `firstName`, IF(LENGTH(`middleName`), `middleName`, NULL), `lastName`) as `fullName`,
                `generation`
            FROM `Member`
            WHERE
                (
                CONCAT(LOWER(`firstName`), ' ', LOWER(`lastName`)) LIKE :name
                OR CONCAT(LOWER(`firstName`), ' ', LOWER(`middleName`), ' ', LOWER(`lastName`)) LIKE :name
                )
                AND deleted = 0
                AND expiration >= NOW()
                AND hidden = 0
            ORDER BY $orderColumn $orderDirection LIMIT :limit
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter(':name', '%' . strtolower($name) . '%')
            ->setParameter(':limit', $maxResults);

        return $query->getArrayResult();
    }

    /**
     * Find all members with a birthday in the next $days days.
     *
     * When $days equals 0 or isn't given, it will give all birthdays of today.
     * We do not show members whose membership has expired or who are hidden
     *
     * @param int $days the number of days to look ahead
     *
     * @return MemberModel[] sorted by birthday
     */
    public function findBirthdayMembers(int $days): array
    {
        // unfortunately, there is no support for functions like DAY() and MONTH()
        // in doctrine2, thus we have to use the NativeSQL here
        $builder = new ResultSetMappingBuilder($this->getEntityManager());
        $builder->addRootEntityFromClassMetadata($this->getRepositoryName(), 'm');

        $select = $builder->generateSelectClause(['m' => 't1']);

        $sql = <<<QUERY
            SELECT $select FROM Member AS t1
            WHERE DATEDIFF(
                DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR),
                DATE_SUB(CURDATE(), INTERVAL YEAR(CURDATE()) YEAR)
            ) BETWEEN 0 AND :days
            AND t1.deleted = 0
            AND t1.expiration >= CURDATE()
            AND t1.hidden = 0
            ORDER BY DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR) ASC
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery($sql, $builder);
        $query->setParameter('days', $days);

        return $query->getResult();
    }

    /**
     * Find all organs of this member.
     *
     * @return OrganModel[]
     */
    public function findOrgans(MemberModel $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT o')
            ->from(OrganModel::class, 'o')
            ->join('o.members', 'om')
            ->join('om.member', 'm')
            ->where('m.lidnr = :lidnr')
            ->andWhere('om.dischargeDate IS NULL');

        $qb->setParameter('lidnr', $member->getLidnr());

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all active installations of a member.
     *
     * @return OrganMemberModel[]
     */
    public function findCurrentInstallations(MemberModel $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('om')
            ->from(OrganMemberModel::class, 'om')
            ->leftJoin('om.organ', 'o')
            ->where('om.member = :member')
            ->andWhere('om.dischargeDate IS NULL');

        $qb->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all past installations of a member.
     *
     * @return OrganMemberModel[]
     */
    public function findHistoricalInstallations(MemberModel $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('om')
            ->from(OrganMemberModel::class, 'om')
            ->leftJoin('om.organ', 'o')
            ->where('om.member = :member')
            ->andWhere('om.dischargeDate IS NOT NULL');

        $qb->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return MemberModel::class;
    }
}
