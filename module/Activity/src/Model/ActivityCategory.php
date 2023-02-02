<?php

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Entity,
    JoinColumn,
    ManyToMany,
    OneToOne,
};

/**
 * Activity Category model.
 */
#[Entity]
class ActivityCategory
{
    use IdentifiableTrait;

    /**
     * The Activities this Category belongs to.
     */
    #[ManyToMany(
        targetEntity: Activity::class,
        mappedBy: "categories",
        cascade: ["persist"],
    )]
    protected Collection $activities;

    /**
     * Name for the Category.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: "name_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected ActivityLocalisedText $name;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    /**
     * @param Activity $activity
     */
    public function addActivity(Activity $activity): void
    {
        if ($this->activities->contains($activity)) {
            return;
        }

        $this->activities->add($activity);
    }

    /**
     * @param Activity $activity
     */
    public function removeActivity(Activity $activity): void
    {
        if (!$this->activities->contains($activity)) {
            return;
        }

        $this->activities->removeElement($activity);
    }

    /**
     * @return ActivityLocalisedText
     */
    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    /**
     * @param ActivityLocalisedText $name
     */
    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getActivities(): array
    {
        return $this->activities->toArray();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
        ];
    }
}
