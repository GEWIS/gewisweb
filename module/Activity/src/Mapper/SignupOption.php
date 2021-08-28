<?php

namespace Activity\Mapper;

use Activity\Model\SignupOption as SignupOptionModel;
use Application\Mapper\BaseMapper;

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
