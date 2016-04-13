<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;

class LoginAttempt
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
     * Persist a login attempt model
     */
    public function persist($loginAttempt)
    {
        $this->em->persist($loginAttempt);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\LoginAttempt');
    }
}
