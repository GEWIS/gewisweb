<?php

namespace Activity\Mapper;

use Activity\Model\ActivityUpdateProposal;
use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

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
