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
     * Find a member by its membership number.
     *
     * @param int $number Membership number
     *
     * @return MemberModel
     */
    public function findByLidnr($email)
    {
        return $this->getRepository()->findOneBy(array('lidnr' => $email));
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Member');
    }
}
