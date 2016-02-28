<?php

namespace Decision\Mapper;

use Doctrine\ORM\EntityManager;

/**
 * Mappers for authorizations.
 */
class Authorization
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
     * Find all authorizations for a meeting
     *
     * @param integer $meetingNumber
     * @param integer $authorizer
     *
     * @return array
     */
    public function find($meetingNumber, $authorizer = null)
    {
        $criteria = ['meetingNumber' => $meetingNumber];
        if (!is_null($authorizer)) {
            $criteria['authorizer'] = $authorizer;
        }

        return $this->getRepository()->findBy($criteria);
    }

    public function persist($authorization)
    {
        $this->em->persist($authorization);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Authorization');
    }
}
