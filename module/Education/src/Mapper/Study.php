<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Education\Model\Study as StudyModel;

/**
 * Mappers for Study.
 *
 * NOTE: Organs will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Study extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return StudyModel::class;
    }
}
