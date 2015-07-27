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
     * @param string $firstName (part of) the first name of a member
     * @param string $lastName (part of) the last name of a member
     *
     * @return array
     */
    public function findByName($firstName, $lastName)
    {
        $qb = $this->getRepository()->createQueryBuilder('m');

        $qb->where('m.firstName LIKE ?1')
            ->orWhere('m.lastName LIKE ?2')
            ->setParameter(1, $firstName . '%')
            ->setParameter(2, $lastName . '%');

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
