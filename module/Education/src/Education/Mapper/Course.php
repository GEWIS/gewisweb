<?php

namespace Education\Mapper;

use Education\Model\Course as CourseModel;
use Doctrine\ORM\EntityManager;

/**
 * Mappers for Course.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class Course
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
    public function persistMultiple(array $courses)
    {
        foreach ($courses as $course) {
            $this->em->persist($course);
        }
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Education\Mapper\Course');
    }
}
