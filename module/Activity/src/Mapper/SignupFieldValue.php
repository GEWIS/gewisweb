<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\{
    Signup as SignupModel,
    SignupFieldValue as SignupFieldValueModel,
};
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<SignupFieldValueModel>
 */
class SignupFieldValue extends BaseMapper
{
    /**
     * Finds all field values associated with the $signup.
     *
     * @return array<array-key, SignupFieldValueModel>
     */
    public function getFieldValuesBySignup(SignupModel $signup): array
    {
        return $this->getRepository()->findBy(['signup' => $signup->getId()]);
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return SignupFieldValueModel::class;
    }
}
