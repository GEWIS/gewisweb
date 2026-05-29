<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Member as MemberModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * One message in the review discussion thread attached to a single revision (board <-> author back-and-forth, e.g.
 * the feedback accompanying a "changes requested" or "rejected" decision).
 *
 * Concrete subclasses (ActivityRevisionComment, JobRevisionComment, ...) declare the typed `revision` association to
 * their own revision table, and MUST declare {@see \Doctrine\ORM\Mapping\HasLifecycleCallbacks} for the timestamp
 * callbacks from {@see TimestampableTrait}.
 */
#[MappedSuperclass]
abstract class AbstractRevisionComment
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The member who wrote this comment.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    private MemberModel $author;

    #[Column(type: Types::TEXT)]
    private string $body;

    public function getAuthor(): MemberModel
    {
        return $this->author;
    }

    public function setAuthor(MemberModel $author): void
    {
        $this->author = $author;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * The revision this comment belongs to.
     */
    abstract public function getRevision(): RevisionInterface;
}
