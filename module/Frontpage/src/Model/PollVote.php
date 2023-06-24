<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll response
 * Represents a vote on a poll option.
 */
#[Entity]
#[UniqueConstraint(
    name: 'vote_idx',
    columns: ['poll_id', 'user_id'],
)]
class PollVote implements ResourceInterface
{
    /**
     * The poll which was voted on.
     */
    #[ManyToOne(targetEntity: Poll::class)]
    #[JoinColumn(
        name: 'poll_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * The option which was chosen.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: PollOption::class,
        inversedBy: 'votes',
    )]
    #[JoinColumn(
        name: 'option_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected PollOption $pollOption;

    /**
     * The user whom submitted this vote.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: MemberModel::class,
        cascade: ['persist'],
    )]
    #[JoinColumn(
        name: 'user_id',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $respondent;

    public function getPollOption(): PollOption
    {
        return $this->pollOption;
    }

    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    public function setPollOption(PollOption $pollOption): void
    {
        $this->pollOption = $pollOption;
    }

    public function setRespondent(MemberModel $respondent): void
    {
        $this->respondent = $respondent;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'poll_response';
    }
}
