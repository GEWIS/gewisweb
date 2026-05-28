<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\ActivityLabelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Activity Label model.
 *
 * @psalm-type ActivityLabelArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-type ActivityLabelGdprArrayType = array{
 *     id: int,
 *     name: ImportedLocalisedTextGdprArrayType,
 * }
 */
#[Entity(repositoryClass: ActivityLabelRepository::class)]
class ActivityLabel
{
    use IdentifiableTrait;

    /**
     * The Activities this Label belongs to.
     *
     * @var Collection<array-key, Activity>
     */
    #[ManyToMany(
        targetEntity: Activity::class,
        mappedBy: 'labels',
        cascade: ['persist'],
    )]
    private Collection $activities;

    /**
     * Name for the Label.
     */
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

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    public function addActivity(Activity $activity): void
    {
        if ($this->activities->contains($activity)) {
            return;
        }

        $this->activities->add($activity);
    }

    public function removeActivity(Activity $activity): void
    {
        if (!$this->activities->contains($activity)) {
            return;
        }

        $this->activities->removeElement($activity);
    }

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Activity[]
     */
    public function getActivities(): array
    {
        return $this->activities->toArray();
    }

    /**
     * @return ActivityLabelArrayType
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
        ];
    }

    /**
     * @return ActivityLabelGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->toGdprArray(),
        ];
    }
}
