<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\MeetingMinutes as MeetingMinutesModel;
use Override;

/**
 * @template-extends BaseMapper<MeetingMinutesModel>
 */
class MeetingMinutes extends BaseMapper
{
    #[Override]
    protected function getRepositoryName(): string
    {
        return MeetingMinutesModel::class;
    }
}
