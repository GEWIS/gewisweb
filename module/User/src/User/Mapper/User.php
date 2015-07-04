<?php

namespace User\Mapper;

use User\Model\User as UserModel;
use User\Model\NewUser as NewUserModel;
use Doctrine\ORM\EntityManager;

class User
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
     * Find a user by its email.
     *
     * @param int $email Email to search by
     *
     * @return UserModel
     */
    public function findByEmail($email)
    {
        return $this->getRepository()->findOneBy(array('email' => $email));
    }

    /**
     * Find a user by its membership number.
     *
     * @param int $number Membership number
     *
     * @return UserModel
     */
    public function findByLidnr($email)
    {
        return $this->getRepository()->findOneBy(array('lidnr' => $email));
    }

    /**
     * Find a user by its login.
     *
     * @param string $login
     *
     * @return void
     */
    public function findByLogin($login)
    {
        // create query for user
        $qb = $this->em->createQueryBuilder();
        $qb->select('u, r, m')
            ->from('User\Model\User', 'u')
            ->leftJoin('u.roles', 'r')
            ->join('u.member', 'm')
			->join('m.organs', 'o');


        // depending on login, add correct where clause
        if (is_numeric($login)) {
            $qb->where('u.lidnr = ?1');
        } else {
            $qb->where('u.email = ?1');
        }

        // set the parameters
        $qb->setParameter(1, $login);
        $qb->setMaxResults(1);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
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
     * @param UserModel $user User to persist.
     */
    public function persist(UserModel $user)
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
        return $this->em->getRepository('User\Model\User');
    }

}
