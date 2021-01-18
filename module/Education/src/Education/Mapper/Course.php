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
     * Persist course
     *
     * @param array $course of CourseModel
     */
    public function persist($course)
    {
        $this->em->persist($course);
        $this->flush();
    }

    /**
     * Flush.
     */
    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Find a course by code.
     *
     * @param string $code
     *
     * @return CourseModel
     */
    public function findByCode($code)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('c, e, p, ch, ce')
            ->from('Education\Model\Course', 'c')
            ->where('c.code = ?1')
            ->leftJoin('c.exams', 'e')
            ->leftJoin('c.parent', 'p')
            ->leftJoin('c.children', 'ch')
            ->leftJoin('ch.exams', 'ce');
        $qb->setParameter(1, $code);

        $res = $qb->getQuery()->getResult();
        return empty($res) ? null : $res[0];
    }

    /**
     * Search for courses.
     *
     * @param string $query
     *
     * @return array
     */
    public function search($query)
    {
        $query = '%' . $query . '%';
        $qb = $this->em->createQueryBuilder();

        $qb->select('c')
            ->from('Education\Model\Course', 'c')
            ->where('c.code LIKE ?1')
            ->orWhere('c.name LIKE ?1');
        $qb->setParameter(1, $query);

        return $qb->getQuery()->getResult();
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
