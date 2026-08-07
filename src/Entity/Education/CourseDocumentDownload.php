<?php

declare(strict_types=1);

namespace App\Entity\Education;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Education\Enums\DownloadStatus;
use App\Entity\User\User;
use App\Repository\Education\CourseDocumentDownloadRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * The watermark names who asked for a download and when, so the request is recorded before the file exists. The row
 * doubles as the handle the browser waits on while the worker builds the file, and as what a leaked copy is traced back
 * to: the same reference goes into the delivered PDF as machine-readable text.
 *
 * Who requested it is snapshotted as {@see $requestedByName} rather than read back off the association, because the
 * watermark has to keep saying what it said at the time even if the account is later renamed or removed.
 */
#[Entity(repositoryClass: CourseDocumentDownloadRepository::class)]
class CourseDocumentDownload
{
    use IdentifiableTrait;

    /** The unguessable handle the download routes are keyed on. */
    #[Column(
        type: UuidType::NAME,
        unique: true,
    )]
    private Uuid $token;

    #[ManyToOne(targetEntity: CourseDocument::class)]
    #[JoinColumn(
        name: 'document_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private CourseDocument $document;

    /** Null for an anonymous request from the campus network. */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'requested_by',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $requestedBy = null;

    /**
     * What the watermark names as the person who downloaded it: the member's full name, or the client address for an
     * anonymous request from the campus network.
     */
    #[Column(type: Types::STRING)]
    private string $requestedByName;

    /**
     * The address the request came from. For an anonymous request from campus this is the only thing tying the built
     * file to whoever asked for it, so it is what the collect routes check.
     */
    #[Column(type: Types::STRING)]
    private string $requestedFrom;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private DateTime $requestedAt;

    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $collectedAt = null;

    #[Column(
        type: Types::STRING,
        enumType: DownloadStatus::class,
    )]
    private DownloadStatus $status = DownloadStatus::Pending;

    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $path = null;

    public function getToken(): Uuid
    {
        return $this->token;
    }

    public function setToken(Uuid $token): void
    {
        $this->token = $token;
    }

    public function getDocument(): CourseDocument
    {
        return $this->document;
    }

    public function setDocument(CourseDocument $document): void
    {
        $this->document = $document;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): void
    {
        $this->requestedBy = $requestedBy;
    }

    public function getRequestedByName(): string
    {
        return $this->requestedByName;
    }

    public function setRequestedByName(string $requestedByName): void
    {
        $this->requestedByName = $requestedByName;
    }

    public function getRequestedFrom(): string
    {
        return $this->requestedFrom;
    }

    public function setRequestedFrom(string $requestedFrom): void
    {
        $this->requestedFrom = $requestedFrom;
    }

    public function getRequestedAt(): DateTime
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(DateTime $requestedAt): void
    {
        $this->requestedAt = $requestedAt;
    }

    public function getCollectedAt(): ?DateTime
    {
        return $this->collectedAt;
    }

    public function setCollectedAt(?DateTime $collectedAt): void
    {
        $this->collectedAt = $collectedAt;
    }

    public function getStatus(): DownloadStatus
    {
        return $this->status;
    }

    public function setStatus(DownloadStatus $status): void
    {
        $this->status = $status;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * Written into the delivered PDF as machine-readable text, so a copy found elsewhere leads back to this row.
     */
    public function getReference(): string
    {
        return $this->token->toRfc4122();
    }

    /**
     * The token is unguessable, but possession of it is not enough on its own: the file it leads to names whoever
     * requested it, so handing the link on would let somebody redistribute a document under another member's name. An
     * anonymous request from campus is only identified by the address it came from, so that is what has to match.
     */
    public function isCollectableBy(
        ?User $user,
        ?string $clientIp,
    ): bool {
        if (null !== $this->requestedBy) {
            return $this->requestedBy->getId() === $user?->getId();
        }

        return null === $user
            && $this->requestedFrom === $clientIp;
    }
}
