<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Override;
use User\Model\ApiApp as ApiAppModel;

/**
 * @template-extends BaseMapper<ApiAppModel>
 */
class ApiApp extends BaseMapper
{
    public function findByAppId(string $appId): ?ApiAppModel
    {
        return $this->getRepository()->findOneBy(
            [
                'appId' => $appId,
            ],
        );
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return ApiAppModel::class;
    }
}
