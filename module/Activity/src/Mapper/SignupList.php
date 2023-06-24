<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\SignupList as SignupListModel;
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<SignupListModel>
 */
class SignupList extends BaseMapper
{
    public function getSignupListByIdAndActivity(
        int $signupListId,
        int $activityId,
    ): ?SignupListModel {
        return $this->findOneBy([
            'id' => $signupListId,
            'activity' => $activityId,
        ]);
    }

    protected function getRepositoryName(): string
    {
        return SignupListModel::class;
    }
}
