<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Frontpage\Model\PollOption as PollOptionModel;

/**
 * Mappers for poll options.
 *
 * @template-extends BaseMapper<PollOptionModel>
 */
class PollOption extends BaseMapper
{
    /**
     * @inheritdoc
     */
    protected function getRepositoryName(): string
    {
        return PollOptionModel::class;
    }
}
