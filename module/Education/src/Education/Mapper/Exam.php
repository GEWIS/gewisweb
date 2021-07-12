<?php

namespace Education\Mapper;

use Closure;
use Doctrine\ORM\EntityRepository;
use Education\Model\Exam as ExamModel;
use Doctrine\ORM\EntityManager;

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
     * Constructor
     *
     * @param EntityManager $em
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
     *
     * @param Closure $func
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
     * Find an exam
     *
     * @param int $id
     * @return ExamModel
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Persist an exam
     *
     * @param ExamModel $exam
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
