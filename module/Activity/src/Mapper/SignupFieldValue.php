<?php

namespace Activity\Mapper;

use Activity\Model\SignupFieldValue as SignupFieldValueModel;
use Application\Mapper\BaseMapper;

class SignupFieldValue extends BaseMapper
{
    /**
     * Finds all field values associated with the $signup.
     *
     * @return array of \Activity\Model\ActivityFieldValue
     */
    public function getFieldValuesBySignup(\Activity\Model\Signup $signup)
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
