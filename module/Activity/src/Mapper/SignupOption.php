<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\SignupOption as SignupOptionModel;
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<SignupOptionModel>
 */
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
