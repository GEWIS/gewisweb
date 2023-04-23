<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal as ActivityUpdateProposalModel;
use Application\Mapper\BaseMapper;

/**
 * @template-extends BaseMapper<ActivityUpdateProposalModel>
 */
class Proposal extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityUpdateProposalModel::class;
    }
}
