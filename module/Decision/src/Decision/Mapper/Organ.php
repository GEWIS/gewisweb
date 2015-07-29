<?php

namespace Decision\Mapper;

use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for organs.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Organ
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
     * Find all active organs.
     *
     * @return array
     */
    public function findActive()
    {
        return $this->getRepository()->findBy(array(
            'abrogationDate' => null
        ));
    }

    /**
     * Find all organs.
     *
     * @return array
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Organ');
    }
}
