<?php

namespace Education\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Education\Model\Study as StudyModel;

/**
 * Mappers for Study.
 *
 * NOTE: Organs will be modified externally by a script. Modifycations will be
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
