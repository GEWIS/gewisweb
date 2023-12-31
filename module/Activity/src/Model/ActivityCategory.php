<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\LocalisedText as LocalisedTextModel;
use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Activity Category model.
 *
 * @psalm-type ActivityCategoryArrayType = array{
 *     id: int,
 *     name: ?string,
 *     nameEn: ?string,
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-type ActivityCategoryGdprArrayType = array{
 *     id: int,
 *     name: ImportedLocalisedTextGdprArrayType,
 * }
 */
#[Entity]
class ActivityCategory
{
    use IdentifiableTrait;

    /**
     * The Activities this Category belongs to.
     *
     * @var Collection<array-key, Activity>
     */
    #[ManyToMany(
        targetEntity: Activity::class,
        mappedBy: 'categories',
        cascade: ['persist'],
    )]
    protected Collection $activities;

    /**
     * Name for the Category.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $name;

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
     * @return ActivityCategoryArrayType
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
     * @return ActivityCategoryGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->toGdprArray(),
        ];
    }
}
