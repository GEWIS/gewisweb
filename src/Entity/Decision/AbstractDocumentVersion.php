<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Entity\User\User;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappedSuperclass;

/**
 * One uploaded file of a versioned document. Versions are ordered by their insertion order (the id), never by parsing
 * the free-form label.
 */
#[MappedSuperclass]
abstract class AbstractDocumentVersion
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * Free-form version label, e.g. "v1.2".
     */
    #[Column(
        type: Types::STRING,
        length: 32,
    )]
    private string $versionLabel;

    /**
     * Path of the file, relative to the storage directory.
     */
    #[Column(type: Types::STRING)]
    private string $path;

    /**
     * The account that uploaded this version. `null` for versions carried over from the legacy flat documents.
     */
    #[ManyToOne(targetEntity: User::class)]
    #[JoinColumn(
        name: 'uploadedBy',
        referencedColumnName: 'lidnr',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?User $uploadedBy = null;

    /**
     * When this version was uploaded. `null` when unknown, which is the case for most versions carried over from the
     * legacy flat documents.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $uploadedAt = null;

    public function getVersionLabel(): string
    {
        return $this->versionLabel;
    }

    public function setVersionLabel(string $versionLabel): void
    {
        $this->versionLabel = $versionLabel;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getUploadedBy(): ?User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?User $uploadedBy): void
    {
        $this->uploadedBy = $uploadedBy;
    }

    public function getUploadedAt(): ?DateTime
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(?DateTime $uploadedAt): void
    {
        $this->uploadedAt = $uploadedAt;
    }
}
