<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabel as JobLabelModel;

/**
 * Mappers for labels.
 */
class Label extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return JobLabeLModel::class;
    }
}
