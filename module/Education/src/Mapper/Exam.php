<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Closure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Education\Model\Exam as ExamModel;

/**
 * Mapper for Exam.
 */
class Exam extends BaseMapper
{
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
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ExamModel::class;
    }
}
