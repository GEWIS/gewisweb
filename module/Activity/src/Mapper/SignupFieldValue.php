<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\Signup as SignupModel;
use Activity\Model\SignupFieldValue as SignupFieldValueModel;
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<SignupFieldValueModel>
 */
class SignupFieldValue extends BaseMapper
{
    /**
     * Finds all field values associated with the $signup.
     *
     * @return SignupFieldValueModel[]
     */
    public function getFieldValuesBySignup(SignupModel $signup): array
    {
        return $this->getRepository()->findBy(['signup' => $signup->getId()]);
    }

    protected function getRepositoryName(): string
    {
        return SignupFieldValueModel::class;
    }
}
