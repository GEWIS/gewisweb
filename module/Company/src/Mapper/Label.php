<?php

declare(strict_types=1);

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\JobLabel as JobLabelModel;

/**
 * Mappers for labels.
 *
 * @template-extends BaseMapper<JobLabelModel>
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
