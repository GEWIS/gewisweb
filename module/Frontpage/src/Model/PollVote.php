<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping\{
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
    UniqueConstraint,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Model\User as UserModel;

/**
 * Poll response
 * Represents a vote on a poll option.
 */
#[Entity]
#[UniqueConstraint(
    name: "vote_idx",
    columns: ["poll_id", "user_id"],
)]
class PollVote implements ResourceInterface
{
    /**
     * The poll which was voted on.
     */
    #[ManyToOne(targetEntity: Poll::class)]
    #[JoinColumn(
        name: "poll_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * The option which was chosen.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: PollOption::class,
        inversedBy: "votes",
    )]
    #[JoinColumn(
        name: "option_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected PollOption $pollOption;

    /**
     * The user whom submitted this vote.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: UserModel::class,
        cascade: ["persist"],
    )]
    #[JoinColumn(
        name: "user_id",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected UserModel $respondent;

    /**
     * @return PollOption
     */
    public function getPollOption(): PollOption
    {
        return $this->pollOption;
    }

    /**
     * @param Poll $poll
     */
    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    /**
     * @param PollOption $pollOption
     */
    public function setPollOption(PollOption $pollOption): void
    {
        $this->pollOption = $pollOption;
    }

    /**
     * @param UserModel $respondent
     */
    public function setRespondent(UserModel $respondent): void
    {
        $this->respondent = $respondent;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'poll_response';
    }
}
