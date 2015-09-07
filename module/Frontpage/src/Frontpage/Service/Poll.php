<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;

/**
 * Poll service.
 */
class Poll extends AbstractAclService
{

    public function getNewestPoll()
    {
        return $this->getPollMapper()->getNewestPoll();
    }

    /**
     * Returns details about a poll.
     *
     * @param \Frontpage\Model\Poll $poll
     * @return array
     */
    public function getPollDetails($poll)
    {
        $totalVotes = 0;
        foreach ($poll->getOptions() as $option) {
            $totalVotes += $option->getVotesCount();
        }

        $percentages = array();
        foreach ($poll->getOptions() as $option) {
            if ($totalVotes > 0) {
                $percentages[$option->getId()] = round($option->getVotesCount() / $totalVotes * 100);
            } else {
                $percentages[$option->getId()] = 0;
            }
        }

        return array(
            'totalVotes' => $totalVotes,
            'percentages' => $percentages
        );
    }

    /**
     * Determines wether the current user can vote on the given poll.
     *
     * @param \Frontpage\Model\Poll $poll
     *
     * @return boolean
     */
    public function canVote($poll)
    {

    }

    /**
     * Get the poll mapper.
     *
     * @return \Frontpage\Mapper\Poll
     */
    public function getPollMapper()
    {
        return $this->sm->get('frontpage_mapper_poll');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'poll';
    }
}
