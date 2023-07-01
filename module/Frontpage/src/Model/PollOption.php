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
use Doctrine\ORM\Mapping\OneToOne;
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
     * The localised text for this option.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'text_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $text;

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

    public function getText(): FrontpageLocalisedText
    {
        return $this->text;
    }

    public function setText(FrontpageLocalisedText $text): void
    {
        $this->text = $text;
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
