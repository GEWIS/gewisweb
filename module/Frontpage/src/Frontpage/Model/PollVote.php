<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll response
 * Represents a vote on a poll option.
 *
 * @ORM\Entity
 * @ORM\Table(name="PollVote",uniqueConstraints={@ORM\UniqueConstraint(name="vote_idx", columns={"poll_id", "user_id"})})
 */
class PollVote implements ResourceInterface
{
    /**
     * The poll which was voted on
     *
     * @ORM\ManyToOne(targetEntity="Frontpage\Model\Poll")
     * @ORM\JoinColumn(name="poll_id",referencedColumnName="id")
     */
    protected $poll;

    /**
     * The option which was chosen.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Frontpage\Model\PollOption", inversedBy="votes")
     * @ORM\JoinColumn(name="option_id",referencedColumnName="id")
     */
    protected $pollOption;

    /**
     * The user whom submitted this vote.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User\Model\User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id",referencedColumnName="lidnr")
     */
    protected $respondent;

    /**
     * @return PollOption
     */
    public function getPollOption()
    {
        return $this->pollOption;
    }

    /**
     * @param Poll $poll
     */
    public function setPoll($poll)
    {
        $this->poll = $poll;
    }

    /**
     * @param PollOption $pollOption
     */
    public function setPollOption($pollOption)
    {
        $this->pollOption = $pollOption;
    }

    /**
     * @param User $respondent
     */
    public function setRespondent($respondent)
    {
        $this->respondent = $respondent;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'poll_response';
    }
}
