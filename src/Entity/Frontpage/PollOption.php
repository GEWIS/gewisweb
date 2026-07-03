<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Frontpage\PollOptionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Poll Option.
 */
#[Entity(repositoryClass: PollOptionRepository::class)]
class PollOption
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
    private Poll $poll;

    /**
     * The localised text for this option.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'text_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private FrontpageLocalisedText $text;

    /**
     * Votes for this option.
     *
     * @var Collection<array-key, PollVote>
     */
    #[OneToMany(
        targetEntity: PollVote::class,
        mappedBy: 'pollOption',
        cascade: [
            'persist',
            'remove',
        ],
        fetch: 'EXTRA_LAZY',
    )]
    private Collection $votes;

    #[Column(
        type: Types::INTEGER,
        options: ['default' => 0],
    )]
    private int $anonymousVotes = 0;

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
}
