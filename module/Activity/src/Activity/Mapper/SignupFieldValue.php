<?php

namespace Activity\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class SignupFieldValue
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
     * Finds all field values associated with the $signup.
     *
     * @return array of \Activity\Model\ActivityFieldValue
     */
    public function getFieldValuesBySignup(\Activity\Model\Signup $signup)
    {
        return $this->getRepository()->findBy(['signup' => $signup->getId()]);
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Activity\Model\SignupFieldValue');
    }
}
