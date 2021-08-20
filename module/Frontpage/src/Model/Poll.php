<?php

namespace Frontpage\Model;

use DateTime;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Model\User as UserModel;

/**
 * Poll.
 */
#[Entity]
class Poll implements ResourceInterface
{
    /**
     * Poll ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

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
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected UserModel $creator;

    /**
     * Who approved this poll. If null then nobody approved it.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(referencedColumnName: "lidnr")]
    protected ?UserModel $approver = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->options = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return UserModel|null
     */
    public function getApprover(): ?UserModel
    {
        return $this->approver;
    }

    /**
     * @return UserModel
     */
    public function getCreator(): UserModel
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
     * @param UserModel $approver
     */
    public function setApprover(UserModel $approver): void
    {
        $this->approver = $approver;
    }

    /**
     * @param UserModel $creator
     */
    public function setCreator(UserModel $creator): void
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
