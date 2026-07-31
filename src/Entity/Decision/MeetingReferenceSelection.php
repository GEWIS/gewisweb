<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Decision\MeetingReferenceSelectionRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * The selection of a {@see ReferenceDocument} for one meeting. Without a pinned version the meeting follows the latest
 * version of the document, so a library update rolls out to it automatically; pinning freezes what members see.
 */
#[Entity(repositoryClass: MeetingReferenceSelectionRepository::class)]
#[HasLifecycleCallbacks]
#[UniqueConstraint(
    name: 'meeting_reference_unique',
    columns: [
        'meeting_type',
        'meeting_number',
        'referenceDocument_id',
    ],
)]
class MeetingReferenceSelection
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ManyToOne(targetEntity: Meeting::class)]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'type',
        nullable: false,
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'number',
        nullable: false,
    )]
    private Meeting $meeting;

    #[ManyToOne(targetEntity: ReferenceDocument::class)]
    #[JoinColumn(
        name: 'referenceDocument_id',
        nullable: false,
    )]
    private ReferenceDocument $referenceDocument;

    /**
     * The version members see for this meeting; `null` follows the latest version of the document.
     */
    #[ManyToOne(targetEntity: ReferenceDocumentVersion::class)]
    #[JoinColumn(
        name: 'pinnedVersion_id',
        nullable: true,
    )]
    private ?ReferenceDocumentVersion $pinnedVersion = null;

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $this->meeting = $meeting;
    }

    public function getReferenceDocument(): ReferenceDocument
    {
        return $this->referenceDocument;
    }

    public function setReferenceDocument(ReferenceDocument $referenceDocument): void
    {
        $this->referenceDocument = $referenceDocument;
    }

    public function getPinnedVersion(): ?ReferenceDocumentVersion
    {
        return $this->pinnedVersion;
    }

    public function setPinnedVersion(?ReferenceDocumentVersion $pinnedVersion): void
    {
        $this->pinnedVersion = $pinnedVersion;
    }

    /**
     * The version members effectively see: the pinned one, or the latest version of the document.
     */
    public function getEffectiveVersion(): ?ReferenceDocumentVersion
    {
        return $this->pinnedVersion ?? $this->referenceDocument->getLatestVersion();
    }
}
