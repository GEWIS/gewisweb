<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Application\AbstractRevision;
use App\Entity\Application\RevisableInterface;
use App\Repository\Activity\ActivityRevisionRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Override;

/**
 * An immutable snapshot of an {@see Activity}'s revisable content for one point in its revision chain. The stable
 * {@see Activity} owns the sign-up graph, organiser, creator and labels; everything that may be revised and reviewed
 * (the localised texts, the schedule, the category and the facility flags) lives here.
 */
#[Entity(repositoryClass: ActivityRevisionRepository::class)]
#[HasLifecycleCallbacks]
class ActivityRevision extends AbstractRevision
{
    /**
     * The activity this revision belongs to.
     */
    #[ManyToOne(
        targetEntity: Activity::class,
        inversedBy: 'revisions',
    )]
    #[JoinColumn(nullable: false)]
    private Activity $activity;

    /**
     * The revision this one supersedes (null for the first revision in the chain).
     */
    #[ManyToOne(targetEntity: self::class)]
    #[JoinColumn(nullable: true)]
    private ?ActivityRevision $previousRevision = null;

    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $name;

    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'location_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $location;

    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'costs_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $costs;

    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'description_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $description;

    // PHP-nullable so a not-yet-filled draft renders an empty field; the column stays NOT NULL and the form's NotBlank
    // constraint guarantees a value before persist, so a saved revision always has a schedule.
    #[Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $beginTime = null;

    #[Column(type: Types::DATETIME_MUTABLE)]
    private ?DateTime $endTime = null;

    #[Column(
        type: Types::STRING,
        enumType: ActivityCategories::class,
    )]
    private ActivityCategories $category;

    #[Column(type: Types::BOOLEAN)]
    private bool $requireGEFLITST = false;

    #[Column(type: Types::BOOLEAN)]
    private bool $requireZettle = false;

    #[Override]
    public function getRevisable(): RevisableInterface
    {
        return $this->activity;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    #[Override]
    public function getPreviousRevision(): ?ActivityRevision
    {
        return $this->previousRevision;
    }

    public function setPreviousRevision(?ActivityRevision $previousRevision): void
    {
        $this->previousRevision = $previousRevision;
    }

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    public function getLocation(): ActivityLocalisedText
    {
        return $this->location;
    }

    public function setLocation(ActivityLocalisedText $location): void
    {
        $this->location = $location;
    }

    public function getCosts(): ActivityLocalisedText
    {
        return $this->costs;
    }

    public function setCosts(ActivityLocalisedText $costs): void
    {
        $this->costs = $costs;
    }

    public function getDescription(): ActivityLocalisedText
    {
        return $this->description;
    }

    public function setDescription(ActivityLocalisedText $description): void
    {
        $this->description = $description;
    }

    public function getBeginTime(): ?DateTime
    {
        return $this->beginTime;
    }

    public function setBeginTime(?DateTime $beginTime): void
    {
        $this->beginTime = $beginTime;
    }

    public function getEndTime(): ?DateTime
    {
        return $this->endTime;
    }

    public function setEndTime(?DateTime $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getCategory(): ActivityCategories
    {
        return $this->category;
    }

    public function setCategory(ActivityCategories $category): void
    {
        $this->category = $category;
    }

    public function getRequireGEFLITST(): bool
    {
        return $this->requireGEFLITST;
    }

    public function setRequireGEFLITST(bool $requireGEFLITST): void
    {
        $this->requireGEFLITST = $requireGEFLITST;
    }

    public function getRequireZettle(): bool
    {
        return $this->requireZettle;
    }

    public function setRequireZettle(bool $requireZettle): void
    {
        $this->requireZettle = $requireZettle;
    }
}
