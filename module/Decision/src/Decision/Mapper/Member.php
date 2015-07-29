<?php

namespace Decision\Mapper;

use Decision\Model\Member as MemberModel;
use Doctrine\ORM\EntityManager;

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
        return $this->getRepository()->findOneBy(array('lidnr' => $number));
    }

    /**
     * Finds members by (part of) their name.
     *
     * @param string $query (part of) the full name of a member
     * @param integer $maxResults
     *
     * @return array
     */
    public function searchByName($query, $maxResults = 32)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('m')
            ->from('Decision\Model\Member', 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->setMaxResults($maxResults)
            ->setFirstResult(0);
        $qb->setParameter(':name', '%' . strtolower($query) . '%');
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
