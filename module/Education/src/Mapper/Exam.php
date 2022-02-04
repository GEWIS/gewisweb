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
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ExamModel::class;
    }
}
