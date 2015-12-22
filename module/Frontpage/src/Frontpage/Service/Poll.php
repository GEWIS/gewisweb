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
    /**
     * Returns the newest approved poll or null if there is none.
     * @return PollModel|null
     */
    public function getNewestPoll()
    {
        return $this->getPollMapper()->getNewestPoll();
    }

    /**
     * Retrieves a poll by its id
     * @param int $pollId the id of the poll to retrieve
     * @return PollModel|null
     */
    public function getPoll($pollId)
    {
        return $this->getPollMapper()->findPollById($pollId);
    }

    /**
     * Retrieves a poll option by its id
     * @param int $optionId The id of the poll option to retrieve
     * @return \Frontpage\Model\PollOption|null
     */
    public function getPollOption($optionId)
    {
        return $this->getPollMapper()->findPollOptionById($optionId);
    }

    /**
     * Returns a paginator adapter for paging through polls.
     *
     * @return \DoctrineORMModule\Paginator\Adapter\DoctrinePaginator
     */
    public function getPaginatorAdapter()
    {
        return $this->getPollMapper()->getPaginatorAdapter();
    }

    /**
     * Returns all polls which are awaiting approval.
     *
     * @return array
     */
    public function getUnapprovedPolls()
    {
        return $this->getPollMapper()->getUnapprovedPolls();
    }

    /**
     * Returns details about a poll.
     *
     * @param \Frontpage\Model\Poll $poll
     * @return array
     */
    public function getPollDetails($poll)
    {
        if (is_null($poll)) {
            return null;
        }

        $canVote = $this->canVote($poll);
        $userVote = $this->getVote($poll);

        return [
            'canVote' => $canVote,
            'userVote' => $userVote
        ];
    }

    /**
     * Determines whether the current user can vote on the given poll.
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
        if ($user instanceof \User\Model\User) {
            return $this->getPollMapper()->findVote($poll->getId(), $user->getLidnr());
        }

        return null;
    }

    /**
     * Stores a vote for the current user.
     *
     * @param \Frontpage\Model\PollOption $pollOption The option to vote on
     * @return bool indicating whether the vote was submitted
     */
    public function submitVote($pollOption)
    {
        $poll = $pollOption->getPoll();
        if (is_null($poll) || is_null($pollOption)) {
            return false;
        }

        if (!$this->canVote($poll)) {
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
     * Saves a new poll request.
     * @param array $data
     * @return bool indicating whether the request succeeded
     */
    public function requestPoll($data)
    {
        $form = $this->getPollForm();
        $poll = new PollModel();
        $form->bind($poll);

        $form->setData($data);
        if (!$form->isValid()) {
            return false;
        }

        $poll->setExpiryDate(new \DateTime());
        $poll->setCreator($this->getUser());
        $pollMapper = $this->getPollMapper();
        $pollMapper->persist($poll);
        $pollMapper->flush();

        return true;
    }

    /**
     * Returns the poll request/creation form.
     *
     * @return \Frontpage\Form\Poll
     */
    public function getPollForm()
    {
        if (!$this->isAllowed('request')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to request polls')
            );
        }

        return $this->sm->get('frontpage_form_poll');
    }

    /**
     * Get the comment form.
     *
     * @return \Frontpage\Form\PollComment
     */
    public function getCommentForm()
    {
        return $this->sm->get('frontpage_form_poll_comment');
    }

    /**
     * Deletes the given poll.
     *
     * @param \Frontpage\Model\Poll $poll The poll to delete
     */
    public function deletePoll($poll)
    {
        if (!$this->isAllowed('delete')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to delete polls')
            );
        }

        $pollMapper = $this->getPollMapper();
        $pollMapper->remove($poll);
        $pollMapper->flush();
    }

    /**
     * Approves the given poll.
     *
     * @param \Frontpage\Model\Poll $poll The poll to approve
     * @param array $data The data from the poll approval form
     * @return bool indicating whether the approval succeeded
     */
    public function approvePoll($poll, $data)
    {
        $approvalForm = $this->getPollApprovalForm();
        $approvalForm->bind($poll);
        $approvalForm->setData($data);
        if (!$approvalForm->isValid()) {
            return false;
        }

        $poll->setApprover($this->getUser());
        $this->getPollMapper()->flush();
    }

    /**
     * Returns the poll approval form.
     *
     * @return \Frontpage\Form\PollApproval
     */
    public function getPollApprovalForm()
    {
        if (!$this->isAllowed('approve')) {
            $translator = $this->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to approve polls')
            );
        }

        return $this->sm->get('frontpage_form_poll_approval');
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
        $user = $this->sm->get('user_role');

        return ($user instanceof \User\Model\User) ? $em->merge($user) : $user;
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
