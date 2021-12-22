<?php

namespace Photo\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Photo\Mapper\Vote as VoteMapper;

/**
 * View helper to aid with determining whether a certain user has voted for a certain photo or not.
 */
class HasVoted extends AbstractHelper
{
    /**
     * @var VoteMapper
     */
    private VoteMapper $voteMapper;

    /**
     * @param VoteMapper $voteMapper
     */
    public function __construct(VoteMapper $voteMapper)
    {
        $this->voteMapper = $voteMapper;
    }

    /**
     * @param int $photoId
     * @param int $lidnr
     *
     * @return bool
     */
    public function __invoke(
        int $photoId,
        int $lidnr,
    ): bool {
        return null !== $this->voteMapper->findVote($photoId, $lidnr);
    }
}
