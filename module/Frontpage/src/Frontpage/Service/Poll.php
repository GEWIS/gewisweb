<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use Frontpage\Model\PollVote as PollVoteModel;
use Frontpage\Model\Poll as PollModel;

/**
 * Poll service.
 */
class Poll extends AbstractAclService
{

    public function getNewestPoll()
    {
        return $this->getPollMapper()->getNewestPoll();
    }

    public function getPoll($pollId)
    {
        return $this->getPollMapper()->findPollById($pollId);
    }

    public function getPollOption($optionId)
    {
        return $this->getPollMapper()->findPollOptionById($optionId);
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

        $canVote = $this->canVote($poll);
        $userVote = $this->getVote($poll);

        return array(
            'totalVotes' => $totalVotes,
            'percentages' => $percentages,
            'canVote' => $canVote,
            'userVote' => $userVote
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
        if (!$this->isAllowed('vote')) {
            return false;
        }

        // Check if poll expires after today
        if ($poll->getExpiryDate() <= (new \DateTime())) {
            return false;
        }

        return is_null($this->getVote($poll));
    }

    /**
     * Retrieves the current user's vote for a given poll.
     * Returns null if the user hasn't voted on the poll.
     * @param \Frontpage\Model\Poll $poll
     *
     * @return \Frontpage\Model\PollVote | null
     */
    public function getVote($poll)
    {
        $user = $this->getUser();
        if($user instanceof \User\Model\User) {
            return $this->getPollMapper()->findVote($poll->getId(), $user->getLidnr());
        } else {
            return null;
        }
    }

    public function submitVote($pollOption)
    {
        $poll = $pollOption->getPoll();
        if(is_null($poll) || is_null($pollOption)) {
            return false;
        }

        if(!$this->canVote($poll)) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to vote on this poll.')
            );
        }

        $pollVote = new PollVoteModel();
        $pollVote->setRespondent($this->getUser());
        $pollVote->setPoll($poll);
        $pollOption->addVote($pollVote);
        $pollMapper = $this->getPollMapper();
        $pollMapper->persist($pollOption);
        $pollMapper->flush();
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
     * Retrieves the currently logged in user.
     *
     * @return \User\Model\User|string
     */
    public function getUser()
    {
        $em = $this->getServiceManager()->get('Doctrine\ORM\EntityManager');
        return $em->merge($this->sm->get('user_role'));
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
