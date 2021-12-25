<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\{
    Member as MemberModel,
    Organ as OrganModel,
};
use Doctrine\ORM\Query\{
    ResultSetMapping,
    ResultSetMappingBuilder,
};

class Member extends BaseMapper
{
    /**
     * Find a member by its membership number.
     *
     * @param int $number Membership number
     *
     * @return MemberModel|null
     */
    public function findByLidnr(int $number): ?MemberModel
    {
        return $this->getRepository()->findOneBy(['lidnr' => $number]);
    }

    /**
     * Finds members (lidnr, full name, and generation) by (part of) their name.
     *
     * @param string $name (part of) the full name of a member
     * @param int $maxResults
     * @param string $orderColumn
     * @param string $orderDirection
     *
     * @return array
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
            SELECT `lidnr`, CONCAT_WS(' ', `firstName`, IF(LENGTH(`middleName`), `middleName`, NULL), `lastName`) as `fullName`, `generation`
            FROM `Member`
            WHERE CONCAT(LOWER(`firstName`), ' ', LOWER(`lastName`)) LIKE :name
            OR CONCAT(LOWER(`firstName`), ' ', LOWER(`middleName`), ' ', LOWER(`lastName`)) LIKE :name
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
     *
     * @param int $days the number of days to look ahead
     *
     * @return array Of members sorted by birthday
     */
    public function findBirthdayMembers(int $days): array
    {
        // unfortunately, there is no support for functions like DAY() and MONTH()
        // in doctrine2, thus we have to use the NativeSQL here
        $builder = new ResultSetMappingBuilder($this->getEntityManager());
        $builder->addRootEntityFromClassMetadata($this->getRepositoryName(), 'm');

        $select = $builder->generateSelectClause(['m' => 't1']);

        $sql = "SELECT $select FROM Member AS t1"
            . ' WHERE DATEDIFF(DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR),'
            . ' DATE_SUB(CURDATE(), INTERVAL YEAR(CURDATE()) YEAR)) BETWEEN 0 AND :days'
            . ' AND t1.expiration >= CURDATE()'
            . 'ORDER BY DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR) ASC';

        $query = $this->getEntityManager()->createNativeQuery($sql, $builder);
        $query->setParameter('days', $days);

        return $query->getResult();
    }

    /**
     * Find all organs of this member.
     *
     * @return array Of organs
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
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return MemberModel::class;
    }
}
