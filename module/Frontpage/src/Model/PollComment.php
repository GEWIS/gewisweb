<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use DateTime;
use DateTimeInterface;
use Decision\Model\Member as MemberModel;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Override;

/**
 * Poll comment.
 *
 * @psalm-type PollCommentGdprArrayType = array{
 *     id: int,
 *     createdOn: string,
 *     author: string,
 *     content: string,
 * }
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
        inversedBy: 'comments',
    )]
    #[JoinColumn(
        name: 'poll_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Poll $poll;

    /**
     * User that posted the comment.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        name: 'user_lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected MemberModel $user;

    /**
     * Author of the comment.
     */
    #[Column(type: 'string')]
    protected string $author;

    /**
     * Comment content.
     */
    #[Column(type: 'text')]
    protected string $content;

    /**
     * Comment date.
     */
    #[Column(type: 'datetime')]
    protected DateTime $createdOn;

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

    /**
     * Get the resource ID.
     */
    #[Override]
    public function getResourceId(): string
    {
        return 'poll_comment';
    }
}
