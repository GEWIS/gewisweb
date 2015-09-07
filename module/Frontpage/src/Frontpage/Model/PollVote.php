<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll response
 * Represents a vote on a poll option.
 *
 * @ORM\Entity
 * @ORM\Table(name="PollVote",uniqueConstraints={@ORM\UniqueConstraint(name="vote_idx", columns={"poll", "respondent"})})
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
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="lidnr")
     */
    protected $respondent;

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