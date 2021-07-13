<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use User\Model\NewUser as NewUserModel;
use User\Model\User as UserModel;

class User
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find a user by its membership number.
     *
     * @param int $lidnr Membership number
     *
     * @return UserModel
     */
    public function findByLidnr($lidnr)
    {
        return $this->getRepository()->findOneBy(['lidnr' => $lidnr]);
    }

    /**
     * Find a user by its login.
     *
     * @param string $login
     *
     * @return UserModel
     */
    public function findByLogin($login)
    {
        // create query for user
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, r, m')
            ->from('User\Model\User', 'u')
            ->leftJoin('u.roles', 'r')
            ->join('u.member', 'm');

        // depending on login, add correct where clause
        if (is_numeric($login)) {
            $qb->where('u.lidnr = ?1');
        } else {
            $qb->where('LOWER(m.email) = ?1');
        }

        // set the parameters
        $qb->setParameter(1, strtolower($login));
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * Detach a user from the entity manager.
     */
    public function detach(UserModel $user)
    {
        $this->em->detach($user);
    }

    /**
     * Re-attach a user to the entity manager.
     *
     * @return UserModel
     */
    public function merge(UserModel $user)
    {
        return $this->em->merge($user);
    }

    /**
     * Finish user creation.
     *
     * This will both destroy the NewUser and create the given user
     *
     * @param UserModel $user User to create
     * @param NewUserModel $newUser NewUser to destroy
     */
    public function createUser(UserModel $user, NewUserModel $newUser)
    {
        $this->em->persist($user);
        $this->em->remove($newUser);
        $this->em->flush();
    }

    /**
     * Persist a user model.
     *
     * @param UserModel $user user to persist
     */
    public function persist(UserModel $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\User');
    }
}
