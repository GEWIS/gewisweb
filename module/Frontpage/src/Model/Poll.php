<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll.
 */
#[Entity]
class Poll implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * The date the poll expires.
     */
    #[Column(type: 'date')]
    protected DateTime $expiryDate;

    /**
     * The localised question for the poll.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'question_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $question;

    /**
     * Poll options.
     *
     * @var Collection<array-key, PollOption>
     */
    #[OneToMany(
        targetEntity: PollOption::class,
        mappedBy: 'poll',
        cascade: ['persist', 'remove'],
    )]
    protected Collection $options;

    /**
     * Poll comments.
     *
     * @var Collection<array-key, PollComment>
     */
    #[OneToMany(
        targetEntity: PollComment::class,
        mappedBy: 'poll',
        cascade: ['persist', 'remove'],
    )]
    protected Collection $comments;

    /**
     * Who approved this poll. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $creator;

    /**
     * Who approved this poll. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: 'lidnr')]
    protected ?MemberModel $approver = null;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getExpiryDate(): DateTime
    {
        return $this->expiryDate;
    }

    public function getQuestion(): FrontpageLocalisedText
    {
        return $this->question;
    }

    /**
     * @return Collection<array-key, PollOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @return Collection<array-key, PollComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    public function setExpiryDate(DateTime $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function setQuestion(FrontpageLocalisedText $question): void
    {
        $this->question = $question;
    }

    /**
     * Adds options to the poll.
     */
    public function addOptions(ArrayCollection $options): void
    {
        foreach ($options as $option) {
            $option->setPoll($this);
            $this->options->add($option);
        }
    }

    public function setApprover(MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * Removes options from the poll.
     */
    public function removeOptions(ArrayCollection $options): void
    {
        foreach ($options as $option) {
            $option->setPoll(null);
            $this->options->removeElement($option);
        }
    }

    /**
     * Add a comment to the poll.
     */
    public function addComment(PollComment $comment): void
    {
        $comment->setPoll($this);
        $this->comments[] = $comment;
    }

    /**
     * Add comments to the poll.
     *
     * @param PollComment[] $comments
     */
    public function addComments(array $comments): void
    {
        foreach ($comments as $comment) {
            $this->addComment($comment);
        }
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'poll';
    }

    /**
     * Check to see if the poll is approved. <br>
     * If no-one approved this poll, this poll is not approved.
     *
     * @return bool true if poll is approved; false otherwise
     */
    public function isApproved(): bool
    {
        return null !== $this->getApprover();
    }

    /**
     * Check to see if the poll is currently displayed.
     */
    public function isActive(): bool
    {
        return $this->getExpiryDate() > new DateTime();
    }
}
