<?php

namespace Activity\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    ManyToMany,
    OneToOne,
};

/**
 * Activity Category model.
 */
#[Entity]
class ActivityCategory
{
    /**
     * Id for the Category.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * The Activities this Category belongs to.
     */
    #[ManyToMany(
        targetEntity: "Activity\Model\Activity",
        mappedBy: "categories",
        cascade: ["persist"],
    )]
    protected Collection $activities;

    /**
     * Name for the Category.
     */
    #[OneToOne(
        targetEntity: "Activity\Model\LocalisedText",
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected LocalisedText $name;

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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return LocalisedText
     */
    public function getName(): LocalisedText
    {
        return $this->name;
    }

    /**
     * @param LocalisedText $name
     */
    public function setName(LocalisedText $name): void
    {
        $this->name = $name->copy();
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
