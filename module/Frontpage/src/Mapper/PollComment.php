<?php

declare(strict_types=1);

namespace Frontpage\Mapper;

use Application\Mapper\BaseMapper;
use Frontpage\Model\PollComment as PollCommentModel;

/**
 * Mappers for poll comments.
 *
 * @template-extends BaseMapper<PollCommentModel>
 */
class PollComment extends BaseMapper
{
    /**
     * @inheritdoc
     */
    protected function getRepositoryName(): string
    {
        return PollCommentModel::class;
    }
}
