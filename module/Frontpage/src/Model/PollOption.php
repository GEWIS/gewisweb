<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll Option.
 */
#[Entity]
class PollOption implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Referenced poll.
     */
    #[ManyToOne(
        targetEntity: Poll::class,
        inversedBy: 'options',
        cascade: ['persist'],
    )]
    #[JoinColumn(
        name: 'poll_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * The dutch text for this option.
     */
    #[Column(type: 'string')]
    protected string $dutchText;

    /**
     * The english translation of the option if available.
     */
    #[Column(type: 'string')]
    protected string $englishText;

    /**
     * Votes for this option.
     *
     * @var Collection<array-key, PollVote>
     */
    #[OneToMany(
        targetEntity: PollVote::class,
        mappedBy: 'pollOption',
        cascade: ['persist', 'remove'],
        fetch: 'EXTRA_LAZY',
    )]
    protected Collection $votes;

    #[Column(
        type: 'integer',
        options: ['default' => 0],
    )]
    protected int $anonymousVotes = 0;

    public function getPoll(): Poll
    {
        return $this->poll;
    }

    public function getDutchText(): string
    {
        return $this->dutchText;
    }

    public function getEnglishText(): string
    {
        return $this->englishText;
    }

    /**
     * Adds a new vote for this poll option.
     */
    public function addVote(PollVote $pollVote): void
    {
        $pollVote->setPollOption($this);
        $this->votes[] = $pollVote;
    }

    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    public function setDutchText(string $dutchText): void
    {
        $this->dutchText = $dutchText;
    }

    public function setEnglishText(string $englishText): void
    {
        $this->englishText = $englishText;
    }

    /**
     * Get the number of votes for this poll option.
     */
    public function getVotesCount(): int
    {
        return $this->votes->count() + $this->getAnonymousVotes();
    }

    public function getAnonymousVotes(): int
    {
        return $this->anonymousVotes;
    }

    public function setAnonymousVotes(int $anonymousVotes): void
    {
        $this->anonymousVotes = $anonymousVotes;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'poll_option';
    }
}
