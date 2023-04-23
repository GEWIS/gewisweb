<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    JoinColumn,
    ManyToOne,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Poll comment.
 */
#[Entity]
class PollComment implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Referenced poll.
     */
    #[ManyToOne(
        targetEntity: Poll::class,
        inversedBy: "comments",
    )]
    #[JoinColumn(
        name: "poll_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * User that posted the comment.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: "user_lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected MemberModel $user;

    /**
     * Author of the comment.
     */
    #[Column(type: "string")]
    protected string $author;

    /**
     * Comment content.
     */
    #[Column(type: "text")]
    protected string $content;

    /**
     * Comment date.
     */
    #[Column(type: "datetime")]
    protected DateTime $createdOn;

    /**
     * Get the poll.
     *
     * @return Poll
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

    /**
     * Get the user.
     *
     * @return MemberModel
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
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Get the creation date.
     *
     * @return DateTime
     */
    public function getCreatedOn(): DateTime
    {
        return $this->createdOn;
    }

    /**
     * Set the creation date.
     *
     * @param DateTime $createdOn
     */
    public function setCreatedOn(DateTime $createdOn): void
    {
        $this->createdOn = $createdOn;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'poll_comment';
    }
}
