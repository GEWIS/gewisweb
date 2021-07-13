<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use Application\Service\Email;
use DateTime;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Frontpage\Form\PollApproval;
use Frontpage\Model\Poll as PollModel;
use Frontpage\Model\PollComment;
use Frontpage\Model\PollOption;
use Frontpage\Model\PollVote as PollVoteModel;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * Poll service.
 */
class Poll extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var Email
     */
    private $emailService;

    /**
     * @var \Frontpage\Mapper\Poll
     */
    private $pollMapper;

    /**
     * @var \Frontpage\Form\Poll
     */
    private $pollForm;

    /**
     * @var \Frontpage\Form\PollComment
     */
    private $pollCommentForm;

    /**
     * @var PollApproval
     */
    private $pollApprovalForm;

    public function __construct(
        Translator $translator,
        $userRole,
        Acl $acl,
        Email $emailService,
        \Frontpage\Mapper\Poll $pollMapper,
        \Frontpage\Form\Poll $pollForm,
        \Frontpage\Form\PollComment $pollCommentForm,
        PollApproval $pollApprovalForm
    )
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->emailService = $emailService;
        $this->pollMapper = $pollMapper;
        $this->pollForm = $pollForm;
        $this->pollCommentForm = $pollCommentForm;
        $this->pollApprovalForm = $pollApprovalForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Returns the newest approved poll or null if there is none.
     *
     * @return PollModel|null
     */
    public function getNewestPoll()
    {
        return $this->pollMapper->getNewestPoll();
    }

    /**
     * Retrieves a poll by its id.
     *
     * @param int $pollId the id of the poll to retrieve
     *
     * @return PollModel|null
     *
     * @throws NotAllowedException if the user isn't allowed to see unapproved polls
     */
    public function getPoll($pollId)
    {
        $poll = $this->pollMapper->findPollById($pollId);
        if (is_null($poll->getApprover()) && !$this->isAllowed('view_unapproved')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to view unnapproved polls'));
        }

        return $poll;
    }

    /**
     * Retrieves a poll option by its id.
     *
     * @param int $optionId The id of the poll option to retrieve
     *
     * @return PollOption|null
     */
    public function getPollOption($optionId)
    {
        return $this->pollMapper->findPollOptionById($optionId);
    }

    /**
     * Returns a paginator adapter for paging through polls.
     *
     * @return DoctrinePaginator
     */
    public function getPaginatorAdapter()
    {
        return $this->pollMapper->getPaginatorAdapter();
    }

    /**
     * Returns all polls which are awaiting approval.
     *
     * @return array
     */
    public function getUnapprovedPolls()
    {
        return $this->pollMapper->getUnapprovedPolls();
    }

    /**
     * Returns details about a poll.
     *
     * @param PollModel $poll
     *
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
            'userVote' => $userVote,
        ];
    }

    /**
     * Determines whether the current user can vote on the given poll.
     *
     * @param PollModel $poll
     *
     * @return bool
     */
    public function canVote($poll)
    {
        if (!$this->isAllowed('vote')) {
            return false;
        }

        // Check if poll expires after today
        if ($poll->getExpiryDate() <= (new DateTime())) {
            return false;
        }

        // check if poll is approved
        if (is_null($poll->getApprover())) {
            return false;
        }

        return is_null($this->getVote($poll));
    }

    /**
     * Retrieves the current user's vote for a given poll.
     * Returns null if the user hasn't voted on the poll.
     *
     * @param PollModel $poll
     *
     * @return PollVoteModel | null
     */
    public function getVote($poll)
    {
        $user = $this->userRole;
        if ($user instanceof User) {
            return $this->pollMapper->findVote($poll->getId(), $user->getLidnr());
        }

        return null;
    }

    /**
     * Stores a vote for the current user.
     *
     * @param PollOption $pollOption The option to vote on
     *
     * @return bool indicating whether the vote was submitted
     */
    public function submitVote($pollOption)
    {
        $poll = $pollOption->getPoll();
        if (is_null($poll) || is_null($pollOption)) {
            return false;
        }

        if (!$this->canVote($poll)) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to vote on this poll.'));
        }

        $pollVote = new PollVoteModel();
        $pollVote->setRespondent($this->userRole);
        $pollVote->setPoll($poll);
        $pollOption->addVote($pollVote);
        $this->pollMapper->persist($pollOption);
        $this->pollMapper->flush();
    }

    /**
     * Creates a comment on the given poll.
     *
     * @param int $pollId
     * @param array $data
     */
    public function createComment($pollId, $data)
    {
        if (!$this->isAllowed('create', 'poll_comment')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create comments on this poll'));
        }

        $poll = $this->getPoll($pollId);

        $form = $this->pollCommentForm;

        $form->bind(new PollComment());
        $form->setData($data);

        if (!$form->isValid()) {
            return;
        }

        $comment = $form->getData();
        $comment->setUser($this->userRole);
        $comment->setCreatedOn(new DateTime());

        $poll->addComment($comment);

        $this->pollMapper->persist($poll);
        $this->pollMapper->flush();

        // reset the form
        $form->setData(['author' => '', 'content' => '']);
    }

    /**
     * Saves a new poll request.
     *
     * @param array $data
     *
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

        $poll->setExpiryDate(new DateTime());
        $poll->setCreator($this->userRole);
        $this->pollMapper->persist($poll);
        $this->pollMapper->flush();

        $this->emailService->sendEmail(
            'poll_creation',
            'email/poll',
            'Er is een nieuwe poll aangevraagd | A new poll has been requested',
            ['poll' => $poll]
        );

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
            throw new NotAllowedException($this->translator->translate('You are not allowed to request polls'));
        }

        return $this->pollForm;
    }

    /**
     * Deletes the given poll.
     *
     * @param PollModel $poll The poll to delete
     */
    public function deletePoll($poll)
    {
        if (!$this->isAllowed('delete')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete polls'));
        }

        // Check to see if poll is approved
        if ($poll->isApproved()) {
            // Instead of removing, set expiry date to 'now' to hide poll.
            $poll->setExpiryDate(new DateTime());
        } else {
            // If not approved, just remove the junk from the database.
            $this->pollMapper->remove($poll);
        }

        $this->pollMapper->flush();
    }

    /**
     * Approves the given poll.
     *
     * @param PollModel $poll The poll to approve
     * @param array $data The data from the poll approval form
     *
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

        $poll->setApprover($this->userRole);
        $this->pollMapper->flush();
    }

    /**
     * Returns the poll approval form.
     *
     * @return PollApproval
     */
    public function getPollApprovalForm()
    {
        if (!$this->isAllowed('approve')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to approve polls'));
        }

        return $this->pollApprovalForm;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
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
