<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use User\Model\ApiApp as ApiAppModel;

class ApiApp extends BaseMapper
{
    /**
     * @param string $appId
     *
     * @return ApiAppModel
     */
    public function findByAppId($appId)
    {
        return $this->getRepository()->findOneBy(
            [
                'appId' => $appId,
            ]
        );
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return 'User\Model\ApiUser';
    }
}
