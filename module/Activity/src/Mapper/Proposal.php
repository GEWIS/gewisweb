<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal as ActivityUpdateProposalModel;
use Application\Mapper\BaseMapper;
use Override;

/**
 * @template-extends BaseMapper<ActivityUpdateProposalModel>
 */
class Proposal extends BaseMapper
{
    #[Override]
    protected function getRepositoryName(): string
    {
        return ActivityUpdateProposalModel::class;
    }
}
