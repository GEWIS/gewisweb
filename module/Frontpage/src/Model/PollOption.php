<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
};
use Doctrine\Common\Collections\Collection;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll Option.
 */
#[Entity]
class PollOption implements ResourceInterface
{
    /**
     * Poll Option ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Referenced poll.
     */
    #[ManyToOne(
        targetEntity: Poll::class,
        inversedBy: "options",
    )]
    #[JoinColumn(
        name: "poll_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * The dutch text for this option.
     */
    #[Column(type: "string")]
    protected string $dutchText;

    /**
     * The english translation of the option if available.
     */
    #[Column(type: "string")]
    protected string $englishText;

    /**
     * Votes for this option.
     */
    #[OneToMany(
        targetEntity: PollVote::class,
        mappedBy: "pollOption",
        cascade: ["persist", "remove"],
        fetch: "EXTRA_LAZY",
    )]
    protected Collection $votes;

    /**
     * Number of votes not bound to a specific user.
     */
    #[Column(type: "integer")]
    protected int $anonymousVotes = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Poll
     */
    public function getPoll(): Poll
    {
        return $this->poll;
    }

    /**
     * @return string
     */
    public function getDutchText(): string
    {
        return $this->dutchText;
    }

    /**
     * @return string
     */
    public function getEnglishText(): string
    {
        return $this->englishText;
    }

    /**
     * Adds a new vote for this poll option.
     *
     * @param PollVote $pollVote
     */
    public function addVote(PollVote $pollVote): void
    {
        $pollVote->setPollOption($this);
        $this->votes[] = $pollVote;
    }

    /**
     * @param Poll $poll
     */
    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    /**
     * @param string $dutchText
     */
    public function setDutchText(string $dutchText): void
    {
        $this->dutchText = $dutchText;
    }

    /**
     * @param string $englishText
     */
    public function setEnglishText(string $englishText): void
    {
        $this->englishText = $englishText;
    }

    /**
     * @param int $votes
     */
    public function setAnonymousVotes(int $votes): void
    {
        $this->anonymousVotes = $votes;
    }

    /**
     * Get the number of votes for this poll option.
     *
     * @return int
     */
    public function getVotesCount(): int
    {
        return $this->anonymousVotes + (is_null($this->votes) ? 0 : $this->votes->count());
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'poll_option';
    }
}
