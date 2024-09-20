<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\OrganInformation as OrganInformationModel;

/**
 * @template-extends BaseMapper<OrganInformationModel>
 */
class OrganInformation extends BaseMapper
{
    protected function getRepositoryName(): string
    {
        return OrganInformationModel::class;
    }
}
