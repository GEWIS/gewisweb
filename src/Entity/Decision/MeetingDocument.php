<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Decision\MeetingDocumentRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\PreUpdate;

/**
 * A meeting document: the stable identity members see, with the actual files as {@see MeetingDocumentVersion}s.
 *
 * A document is usually filed under an agenda point; without one it renders in the meeting-level group (documents
 * carried over from the legacy flat model, or whose point was deleted).
 */
#[Entity(repositoryClass: MeetingDocumentRepository::class)]
#[HasLifecycleCallbacks]
class MeetingDocument
{
    use IdentifiableTrait;
    use TimestampableTrait;

    #[ManyToOne(
        targetEntity: Meeting::class,
        inversedBy: 'documents',
    )]
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

    /**
     * The agenda point this document is filed under. The database falls back to `SET NULL` when a point is removed;
     * the service additionally reorders the documents into the meeting-level group.
     */
    #[ManyToOne(
        targetEntity: MeetingPoint::class,
        inversedBy: 'documents',
    )]
    #[JoinColumn(
        name: 'point_id',
        nullable: true,
        onDelete: 'SET NULL',
    )]
    private ?MeetingPoint $point = null;

    /**
     * Name of the document.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * Determines the order in which to display the document within its agenda point or the meeting-level group.
     */
    #[Column(
        type: Types::INTEGER,
        options: ['default' => 0],
    )]
    private int $displayPosition = 0;

    /**
     * The versions of this document, in upload order; members see the last one.
     *
     * @var Collection<array-key, MeetingDocumentVersion>
     */
    #[OneToMany(
        targetEntity: MeetingDocumentVersion::class,
        mappedBy: 'document',
    )]
    #[OrderBy(value: ['id' => 'ASC'])]
    private Collection $versions;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
    }

    public function getMeeting(): Meeting
    {
        return $this->meeting;
    }

    public function setMeeting(Meeting $meeting): void
    {
        $meeting->addDocument($this);
        $this->meeting = $meeting;
    }

    public function getPoint(): ?MeetingPoint
    {
        return $this->point;
    }

    public function setPoint(?MeetingPoint $point): void
    {
        $this->point = $point;
    }

    /**
     * Get the name of the document.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of the document.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDisplayPosition(): int
    {
        return $this->displayPosition;
    }

    public function setDisplayPosition(int $position): void
    {
        $this->displayPosition = $position;
    }

    /**
     * @return Collection<array-key, MeetingDocumentVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(MeetingDocumentVersion $version): void
    {
        $this->versions[] = $version;
    }

    public function getLatestVersion(): ?MeetingDocumentVersion
    {
        $latest = $this->versions->last();

        return false === $latest
            ? null
            : $latest;
    }

    /**
     * Override the `preUpdate` lifecycle callback to prevent updating the timestamp when changing the display position.
     */
    #[PreUpdate]
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('displayPosition')) {
            return;
        }

        $this->setUpdatedAt(new DateTime());
    }
}
