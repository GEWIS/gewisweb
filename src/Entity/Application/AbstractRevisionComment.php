<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\User\CompanyUser as CompanyUserModel;
use App\Entity\User\User as UserModel;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use LogicException;

/**
 * One message in the review discussion thread attached to a single revision (board <-> author back-and-forth, e.g.
 * the feedback accompanying a "changes requested" or "rejected" decision).
 *
 * The author is the authenticated principal who wrote it. Either a member's {@see UserModel} account or (on the company
 * portal) a {@see CompanyUserModel}; exactly one of the two is set, mirroring how a revision records its author.
 *
 * Concrete subclasses MUST declare the typed `revision` association for their own revision table, and MUST also declare
 * {@see \Doctrine\ORM\Mapping\HasLifecycleCallbacks} for the timestamp callbacks from {@see TimestampableTrait} and
 * {@see AbstractRevisionComment::assertSingleAuthor()}.
 */
#[MappedSuperclass]
abstract class AbstractRevisionComment
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The user (a member's account) who wrote this comment. Mutually exclusive with {@see $authorCompanyUser}.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?UserModel $author = null;

    /**
     * The company user who wrote this comment (careers portal). Mutually exclusive with {@see $author}.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?CompanyUserModel $authorCompanyUser = null;

    #[Column(type: Types::TEXT)]
    private string $body;

    public function getAuthor(): ?UserModel
    {
        return $this->author;
    }

    public function setAuthor(?UserModel $author): void
    {
        $this->author = $author;
    }

    public function getAuthorCompanyUser(): ?CompanyUserModel
    {
        return $this->authorCompanyUser;
    }

    public function setAuthorCompanyUser(?CompanyUserModel $authorCompanyUser): void
    {
        $this->authorCompanyUser = $authorCompanyUser;
    }

    /**
     * A human-readable name for whoever wrote this comment, whether a member's account or a company user.
     */
    public function getAuthorDisplayName(): string
    {
        if (null !== $this->author) {
            return $this->author->getMember()->getFullName();
        }

        if (null !== $this->authorCompanyUser) {
            return $this->authorCompanyUser->getCompany()->getName();
        }

        return '';
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
     * Enforce that a comment has exactly one author; never both and never neither.
     *
     * Should be registered as a callback on every concrete subclass via its
     *
     * @see \Doctrine\ORM\Mapping\HasLifecycleCallbacks}.
     */
    #[PrePersist]
    #[PreUpdate]
    public function assertSingleAuthor(): void
    {
        if ((null === $this->author) === (null === $this->authorCompanyUser)) {
            throw new LogicException('A revision comment must have exactly one author (a user or a company user).');
        }
    }

    /**
     * The revision this comment belongs to.
     */
    abstract public function getRevision(): RevisionInterface;
}
