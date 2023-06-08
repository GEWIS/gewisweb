<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityCategory as ActivityCategoryModel;
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<ActivityCategoryModel>
 */
class ActivityCategory extends BaseMapper
{
    protected function getRepositoryName(): string
    {
        return ActivityCategoryModel::class;
    }
}
