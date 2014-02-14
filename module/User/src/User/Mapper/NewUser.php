<?php

namespace User\Mapper;

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
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\NewUser');
    }
}
