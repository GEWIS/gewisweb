<?php

namespace Education\Mapper;

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
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Education\Mapper\Exam');
    }
}

