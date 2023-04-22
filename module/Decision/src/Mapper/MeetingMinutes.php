<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\MeetingMinutes as MeetingMinutesModel;

/**
 * @template-extends BaseMapper<MeetingMinutesModel>
 */
class MeetingMinutes extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return MeetingMinutesModel::class;
    }
}
