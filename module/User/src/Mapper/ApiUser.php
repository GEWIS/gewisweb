<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\ApiUser as ApiUserModel;

class ApiUser extends BaseMapper
{
    /**
     * Find an API user by its token.
     *
     * @param string $token Token of the user
     *
     * @return ApiUserModel|null
     */
    public function findByToken(string $token): ?ApiUserModel
    {
        return $this->getRepository()->findOneBy(
            [
                'token' => $token,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ApiUserModel::class;
    }
}
