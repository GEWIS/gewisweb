<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
    OneToMany,
};
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
    #[Column(type: "date")]
    protected DateTime $expiryDate;

    /**
     * The dutch question for the poll.
     */
    #[Column(type: "string")]
    protected string $dutchQuestion;

    /**
     * The english question for the poll.
     */
    #[Column(type: "string")]
    protected string $englishQuestion;

    /**
     * Poll options.
     */
    #[OneToMany(
        targetEntity: PollOption::class,
        mappedBy: "poll",
        cascade: ["persist", "remove"],
    )]
    protected Collection $options;

    /**
     * Poll comments.
     */
    #[OneToMany(
        targetEntity: PollComment::class,
        mappedBy: "poll",
        cascade: ["persist", "remove"],
    )]
    protected Collection $comments;

    /**
     * Who approved this poll. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $creator;

    /**
     * Who approved this poll. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?MemberModel $approver = null;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * @return DateTime
     */
    public function getExpiryDate(): DateTime
    {
        return $this->expiryDate;
    }

    /**
     * @return string
     */
    public function getDutchQuestion(): string
    {
        return $this->dutchQuestion;
    }

    /**
     * @return string
     */
    public function getEnglishQuestion(): string
    {
        return $this->englishQuestion;
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @return Collection
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * @return MemberModel|null
     */
    public function getApprover(): ?MemberModel
    {
        return $this->approver;
    }

    /**
     * @return MemberModel
     */
    public function getCreator(): MemberModel
    {
        return $this->creator;
    }

    /**
     * @param DateTime $expiryDate
     */
    public function setExpiryDate(DateTime $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    /**
     * @param string $englishQuestion
     */
    public function setEnglishQuestion(string $englishQuestion): void
    {
        $this->englishQuestion = $englishQuestion;
    }

    /**
     * @param string $dutchQuestion
     */
    public function setDutchQuestion(string $dutchQuestion): void
    {
        $this->dutchQuestion = $dutchQuestion;
    }

    /**
     * Adds options to the poll.
     *
     * @param ArrayCollection $options
     */
    public function addOptions(ArrayCollection $options): void
    {
        foreach ($options as $option) {
            $option->setPoll($this);
            $this->options->add($option);
        }
    }

    /**
     * @param MemberModel $approver
     */
    public function setApprover(MemberModel $approver): void
    {
        $this->approver = $approver;
    }

    /**
     * @param MemberModel $creator
     */
    public function setCreator(MemberModel $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * Removes options from the poll.
     *
     * @param ArrayCollection $options
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
     *
     * @param PollComment $comment
     */
    public function addComment(PollComment $comment): void
    {
        $comment->setPoll($this);
        $this->comments[] = $comment;
    }

    /**
     * Add comments to the poll.
     *
     * @param array $comments
     */
    public function addComments(array $comments): void
    {
        foreach ($comments as $comment) {
            $this->addComment($comment);
        }
    }

    /**
     * Get the resource ID.
     *
     * @return string
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
