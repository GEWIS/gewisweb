<?php

namespace Activity\Mapper;

use Activity\Model\{
    Signup as SignupModel,
    SignupFieldValue as SignupFieldValueModel,
};
use Application\Mapper\BaseMapper;

class SignupFieldValue extends BaseMapper
{
    /**
     * Finds all field values associated with the $signup.
     *
     * @return array of \Activity\Model\ActivityFieldValue
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
