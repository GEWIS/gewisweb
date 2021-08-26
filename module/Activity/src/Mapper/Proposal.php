<?php

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal;
use Application\Mapper\BaseMapper;

class Proposal extends BaseMapper
{
    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityUpdateProposal::class;
    }
}
