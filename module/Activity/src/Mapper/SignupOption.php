<?php

namespace Activity\Mapper;

use Activity\Model\SignupOption as SignupOptionModel;
use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class SignupOption extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return SignupOptionModel::class;
    }
}
