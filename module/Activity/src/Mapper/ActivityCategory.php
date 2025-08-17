<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityCategory as ActivityCategoryModel;
use Application\Mapper\BaseMapper;
use Override;

/**
 * @template-extends BaseMapper<ActivityCategoryModel>
 */
class ActivityCategory extends BaseMapper
{
    #[Override]
    protected function getRepositoryName(): string
    {
        return ActivityCategoryModel::class;
    }
}
