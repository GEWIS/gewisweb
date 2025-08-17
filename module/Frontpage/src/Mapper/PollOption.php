<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Frontpage\Model\PollOption as PollOptionModel;
use Override;

/**
 * Mappers for poll options.
 *
 * @template-extends BaseMapper<PollOptionModel>
 */
class PollOption extends BaseMapper
{
    #[Override]
    protected function getRepositoryName(): string
    {
        return PollOptionModel::class;
    }
}
