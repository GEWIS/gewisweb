<?php

namespace User\Mapper;

use Decision\Model\Member;
use User\Model\NewUser as NewUserModel;
use Doctrine\ORM\EntityManager;

class NewUser
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
     * Get the new user by lidnr.
     *
     * @param int $lidnr
     *
     * @return NewUserModel
     */
    public function getByLidnr($lidnr)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, m')
            ->from('User\Model\NewUser', 'u')
            ->join('u.member', 'm')
            ->where('u.lidnr = ?1');
        $qb->setParameter(1, $lidnr);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Get the new user by code.
     *
     * @param string $code
     *
     * @return NewUserModel
     */
    public function getByCode($code)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, m')
            ->from('User\Model\NewUser', 'u')
            ->join('u.member', 'm')
            ->where('u.code = ?1');
        $qb->setParameter(1, $code);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Delete the existing activation code for a member
     *
     * @param Member $member
     * @return array
     */
    public function deleteByMember(Member $member)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete('User\Model\NewUser', 'u');
        $qb->where('u.member = :member');
        $qb->setParameter('member', $member);
        return $qb->getQuery()->getResult();
    }

    /**
     * Persist a user model.
     *
     * @param NewUserModel $user User to persist.
     */
    public function persist(NewUserModel $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\NewUser');
    }
}
