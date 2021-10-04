<?php

namespace Activity\Mapper;

use Activity\Model\ActivityCategory as ActivityCategoryModel;
use Application\Mapper\BaseMapper;

class ActivityCategory extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityCategoryModel::class;
    }
}
