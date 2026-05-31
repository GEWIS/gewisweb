<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\Decision\Member as MemberModel;
use App\Entity\User\CompanyUser as CompanyUserModel;
use App\Entity\User\User as UserModel;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Version;
use LogicException;
use Override;

/**
 * Shared base for every revision entity. Carries the workflow fields common to all revisable domains; the concrete
 * subclasses add the domain-specific content snapshot, the typed back-reference to their aggregate, and the
 * self-referencing `previousRevision` link.
 *
 * Only unidirectional, owning-side associations to a *concrete* entity may live on a mapped superclass, so `author`
 * and `reviewer` (both -> {@see MemberModel}) are declared here; `previousRevision` (a self-reference) and the
 * aggregate back-reference are declared per subclass.
 *
 * Concrete subclasses MUST declare {@see \Doctrine\ORM\Mapping\HasLifecycleCallbacks} so the timestamp callbacks from
 * {@see TimestampableTrait} are registered.
 */
#[MappedSuperclass]
abstract class AbstractRevision implements RevisionInterface
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[Column(
        type: Types::STRING,
        enumType: RevisionStatus::class,
    )]
    private RevisionStatus $status = RevisionStatus::Draft;

    #[Column(type: Types::INTEGER)]
    private int $revisionNumber = 1;

    /**
     * The member who authored this revision (for member-authored domains such as activities, and for revisions a
     * board/C4 member drafts on behalf of a company). Mutually exclusive with {@see $authorCompanyUser}.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?MemberModel $author = null;

    /**
     * The company user who authored this revision (a company drafting for its own vacancy/profile). Mutually
     * exclusive with {@see $author}.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?CompanyUserModel $authorCompanyUser = null;

    /**
     * The member who last reviewed this revision (approved/rejected/requested changes), if any.
     */
    #[ManyToOne(targetEntity: MemberModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?MemberModel $reviewer = null;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $reviewedAt = null;

    /**
     * Optimistic-locking version, bumped on every flush. A backstop against lost updates if two edits ever race past
     * the edit lock ({@see \App\Service\Application\EditLockService}): the second flush fails with an
     * OptimisticLockException, which the edit controller turns into a "changed by someone else" message.
     */
    #[Version]
    #[Column(
        type: Types::INTEGER,
        options: ['default' => 1],
    )]
    private int $version = 1;

    /**
     * The user (a member's account) who last saved an edit to this revision. Unlike {@see $author} (fixed when the
     * draft is spawned) this reflects in-place edits by a co-owner. Mutually exclusive with
     * {@see $lastEditedByCompanyUser}.
     */
    #[ManyToOne(targetEntity: UserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    private ?UserModel $lastEditedBy = null;

    /**
     * The company user who last saved an edit (careers portal). Mutually exclusive with {@see $lastEditedBy}.
     */
    #[ManyToOne(targetEntity: CompanyUserModel::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: true,
    )]
    private ?CompanyUserModel $lastEditedByCompanyUser = null;

    #[Override]
    public function getStatus(): RevisionStatus
    {
        return $this->status;
    }

    #[Override]
    public function setStatus(RevisionStatus $status): void
    {
        $this->status = $status;
    }

    #[Override]
    public function getRevisionNumber(): int
    {
        return $this->revisionNumber;
    }

    #[Override]
    public function setRevisionNumber(int $revisionNumber): void
    {
        $this->revisionNumber = $revisionNumber;
    }

    #[Override]
    public function getAuthor(): ?MemberModel
    {
        return $this->author;
    }

    #[Override]
    public function setAuthor(?MemberModel $author): void
    {
        $this->author = $author;
    }

    #[Override]
    public function getAuthorCompanyUser(): ?CompanyUserModel
    {
        return $this->authorCompanyUser;
    }

    public function setAuthorCompanyUser(?CompanyUserModel $authorCompanyUser): void
    {
        $this->authorCompanyUser = $authorCompanyUser;
    }

    /**
     * A human-readable name for whoever authored this revision, regardless of whether that was a member or a company.
     */
    #[Override]
    public function getAuthorDisplayName(): string
    {
        if (null !== $this->author) {
            return $this->author->getFullName();
        }

        if (null !== $this->authorCompanyUser) {
            return $this->authorCompanyUser->getCompany()->getName();
        }

        return '';
    }

    #[Override]
    public function getReviewer(): ?MemberModel
    {
        return $this->reviewer;
    }

    #[Override]
    public function setReviewer(?MemberModel $reviewer): void
    {
        $this->reviewer = $reviewer;
    }

    #[Override]
    public function getReviewedAt(): ?DateTime
    {
        return $this->reviewedAt;
    }

    #[Override]
    public function setReviewedAt(?DateTime $reviewedAt): void
    {
        $this->reviewedAt = $reviewedAt;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getLastEditedBy(): ?UserModel
    {
        return $this->lastEditedBy;
    }

    public function setLastEditedBy(?UserModel $lastEditedBy): void
    {
        $this->lastEditedBy = $lastEditedBy;
    }

    public function getLastEditedByCompanyUser(): ?CompanyUserModel
    {
        return $this->lastEditedByCompanyUser;
    }

    public function setLastEditedByCompanyUser(?CompanyUserModel $lastEditedByCompanyUser): void
    {
        $this->lastEditedByCompanyUser = $lastEditedByCompanyUser;
    }

    /**
     * A human-readable name for whoever last edited this revision, or null if it has never been edited in place.
     */
    public function getLastEditorDisplayName(): ?string
    {
        if (null !== $this->lastEditedBy) {
            return $this->lastEditedBy->getMember()->getFullName();
        }

        if (null !== $this->lastEditedByCompanyUser) {
            return $this->lastEditedByCompanyUser->getCompany()->getName();
        }

        return null;
    }

    /**
     * Enforce the documented invariant that a revision is never authored, nor last edited, by both a member and a
     * company user at once.
     */
    #[PrePersist]
    #[PreUpdate]
    public function assertSingleActor(): void
    {
        if (
            null !== $this->author
            && null !== $this->authorCompanyUser
        ) {
            throw new LogicException('A revision cannot be authored by both a member and a company user.');
        }

        if (
            null !== $this->lastEditedBy
            && null !== $this->lastEditedByCompanyUser
        ) {
            throw new LogicException('A revision cannot be last edited by both a member and a company user.');
        }
    }
}
