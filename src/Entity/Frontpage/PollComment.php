<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Repository\Frontpage\PollCommentRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

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
    public function getResourceId(): string
    {
        return 'poll_comment';
    }
}
