<?php

namespace Education\Mapper;

use Closure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Education\Model\Exam as ExamModel;

/**
 * Mapper for Exam.
 */
class Exam
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
     * Transactional Doctrine wrapper.
     *
     * Instead of the EntityManager, this inserts this Mapper into the
     * function.
     */
    public function transactional(Closure $func)
    {
        return $this->em->transactional(
            function ($em) use ($func) {
                return $func($this);
            }
        );
    }

    /**
     * Find an exam.
     *
     * @param int $id
     *
     * @return ExamModel
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Persist an exam.
     */
    public function persist(ExamModel $exam)
    {
        $this->em->persist($exam);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Education\Model\Exam');
    }
}
