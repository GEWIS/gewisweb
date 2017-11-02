<?php

namespace Decision\Mapper;

use Decision\Model\Member as MemberModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class Member
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;


    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find a member by its membership number.
     *
     * @param int $number Membership number
     *
     * @return MemberModel
     */
    public function findByLidnr($number)
    {
        return $this->getRepository()->findOneBy(['lidnr' => $number]);
    }

    /**
     * Finds members by (part of) their name.
     *
     * @param string $query (part of) the full name of a member
     * @param integer $maxResults
     *
     * @return array
     */
    public function searchByName($query, $maxResults = 32, $orderColumn = 'generation', $orderDirection = 'DESC')    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('Decision\Model\Member', 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->setMaxResults($maxResults)
            ->orderBy("m.$orderColumn", $orderDirection)
            ->setFirstResult(0);
        $qb->setParameter(':name', '%' . strtolower($query) . '%');
        return $qb->getQuery()->getResult();
    }

    /**
     * Find all members with a birthday in the next $days days.
     *
     * When $days equals 0 or isn't given, it will give all birthdays of today.
     *
     * @param int $days The number of days to look ahead.
     *
     * @return array Of members sorted by birthday
     */
    public function findBirthdayMembers($days)
    {
        // unfortunately, there is no support for functions like DAY() and MONTH()
        // in doctrine2, thus we have to use the NativeSQL here
        $builder = new ResultSetMappingBuilder($this->em);
        $builder->addRootEntityFromClassMetadata('Decision\Model\Member', 'm');

        $select = $builder->generateSelectClause(['m' => 't1']);

        $sql = "SELECT $select FROM Member AS t1"
            . " WHERE DATEDIFF(DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR),"
            . " DATE_SUB(CURDATE(), INTERVAL YEAR(CURDATE()) YEAR)) BETWEEN 0 AND :days"
            . " AND t1.expiration >= CURDATE()"
            . "ORDER BY DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR) ASC";

        $query = $this->em->createNativeQuery($sql, $builder);
        $query->setParameter('days', $days);

        return $query->getResult();
    }

    /**
     * Find all organs of this member.
     *
     * @param MemberModel $member
     *
     * @return array Of organs
     */
    public function findOrgans(MemberModel $member)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('DISTINCT o')
            ->from('Decision\Model\Organ', 'o')
            ->join('o.members', 'om')
            ->join('om.member', 'm')
            ->where('m.lidnr = :lidnr')
            ->andWhere('om.dischargeDate IS NULL');

        $qb->setParameter('lidnr', $member->getLidnr());

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist a member model.
     *
     * @param MemberModel $member Member to persist.
     */
    public function persist(MemberModel $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Member');
    }
}
