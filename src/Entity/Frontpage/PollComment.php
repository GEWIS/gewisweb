<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\Frontpage\Enums\PollCommentReactionType;
use App\Repository\Frontpage\PollCommentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * What a member wrote underneath a poll. Members sign their comment with a name of their own choosing, and only the
 * board gets to see which member that was.
 *
 * A comment either stands on its own or answers another, however deep that goes.
 *
 * @phpstan-type PollCommentGdprArrayType = array{
 *     id: ?int,
 *     createdOn: string,
 *     author: string,
 *     content: string,
 * }
 */
#[Entity(repositoryClass: PollCommentRepository::class)]
class PollComment
{
    use IdentifiableTrait;

    /**
     * Referenced poll.
     */
    #[ManyToOne(
        targetEntity: Poll::class,
        inversedBy: 'comments',
    )]
    #[JoinColumn(
        name: 'poll_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Poll $poll;

    #[ManyToOne(
        targetEntity: self::class,
        inversedBy: 'replies',
    )]
    #[JoinColumn(
        name: 'parent_id',
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?PollComment $parent = null;

    /** @var Collection<array-key, PollComment> */
    #[OneToMany(
        targetEntity: self::class,
        mappedBy: 'parent',
        cascade: [
            'persist',
            'remove',
        ],
    )]
    #[OrderBy(['createdOn' => 'ASC'])]
    private Collection $replies;

    /** @var Collection<array-key, PollCommentReaction> */
    #[OneToMany(
        targetEntity: PollCommentReaction::class,
        mappedBy: 'comment',
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    private Collection $reactions;

    /**
     * User that posted the comment.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'user_lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $user;

    /**
     * Author of the comment.
     */
    #[Column(type: Types::STRING)]
    private string $author;

    /**
     * Comment content.
     */
    #[Column(type: Types::TEXT)]
    private string $content;

    /**
     * Comment date.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $createdOn;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->reactions = new ArrayCollection();
    }

    /**
     * Get the poll.
     */
    public function getPoll(): Poll
    {
        return $this->poll;
    }

    /**
     * Set the poll.
     */
    public function setPoll(Poll $poll): void
    {
        $this->poll = $poll;
    }

    public function getParent(): ?PollComment
    {
        return $this->parent;
    }

    public function setParent(?PollComment $parent): void
    {
        $this->parent = $parent;
        $parent?->addReply($this);
    }

    /**
     * @return Collection<array-key, PollComment>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    /**
     * Kept in step with {@see self::setParent()} so a reply shows up under the comment it answers straight away,
     * rather than only after the thread is read from the database again.
     */
    public function addReply(PollComment $reply): void
    {
        if ($this->replies->contains($reply)) {
            return;
        }

        $this->replies->add($reply);
    }

    /**
     * @return Collection<array-key, PollCommentReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    public function addReaction(PollCommentReaction $reaction): void
    {
        if ($this->reactions->contains($reaction)) {
            return;
        }

        $this->reactions->add($reaction);
        $reaction->setComment($this);
    }

    public function removeReaction(PollCommentReaction $reaction): void
    {
        $this->reactions->removeElement($reaction);
    }

    /**
     * @return array<string, int>
     */
    public function getReactionCounts(): array
    {
        $counts = [];

        foreach ($this->reactions as $reaction) {
            $type = $reaction->getType()->value;
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return $counts;
    }

    public function getReactionOf(MemberModel $member): ?PollCommentReactionType
    {
        foreach ($this->reactions as $reaction) {
            if ($reaction->getMember()?->getLidnr() !== $member->getLidnr()) {
                continue;
            }

            return $reaction->getType();
        }

        return null;
    }

    /**
     * Get the user.
     */
    public function getUser(): MemberModel
    {
        return $this->user;
    }

    /**
     * Set the user.
     */
    public function setUser(MemberModel $user): void
    {
        $this->user = $user;
    }

    /**
     * Get the author.
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Set the author.
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Get the creation date.
     */
    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    /**
     * Set the creation date.
     */
    public function setCreatedOn(DateTime $createdOn): void
    {
        $this->createdOn = $createdOn;
    }

    /**
     * @return PollCommentGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'createdOn' => $this->getCreatedOn()->format(DateTimeInterface::ATOM),
            'author' => $this->getAuthor(),
            'content' => $this->getContent(),
        ];
    }
}
