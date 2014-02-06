<?php

namespace User\Mapper;

use User\Model\User as UserModel;
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
    public function findByNumber($email)
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
        if (is_numeric($login)) {
            return $this->findByNumber($login);
        }
        return $this->findByEmail($login);
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
