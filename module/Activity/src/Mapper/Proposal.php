<?php

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal as ActivityUpdateProposalModel;
use Application\Mapper\BaseMapper;

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
