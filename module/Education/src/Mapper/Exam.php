<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Closure;
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
     *
     * @param Closure $func
     *
     * @return mixed
     */
    public function transactional(Closure $func): mixed
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
