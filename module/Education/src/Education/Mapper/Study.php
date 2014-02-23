<?php

namespace Education\Mapper;

use Education\Model\Study as StudyModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for Study.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Study
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
     * Persist multiple studies
     *
     * @param array $studies Array of StudyModel
     */
    public function persistMultiple(array $studies)
    {
        foreach ($studies as $study) {
            $this->em->persist($study);
        }
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Education\Mapper\Study');
    }
}
