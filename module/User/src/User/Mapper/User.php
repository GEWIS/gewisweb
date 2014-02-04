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
     * Persist a user model.
     *
     * @param UserModel $user User to persist.
     */
    public function persist(UserModel $user)
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
