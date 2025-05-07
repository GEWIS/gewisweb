<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Override;
use User\Model\ApiUser as ApiUserModel;

/**
 * @template-extends BaseMapper<ApiUserModel>
 */
class ApiUser extends BaseMapper
{
    /**
     * Find an API user by its token.
     *
     * @param string $token Token of the user
     */
    public function findByToken(string $token): ?ApiUserModel
    {
        return $this->getRepository()->findOneBy(
            [
                'token' => $token,
            ],
        );
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return ApiUserModel::class;
    }
}
