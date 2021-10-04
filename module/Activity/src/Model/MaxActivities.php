<?php

namespace Activity\Model;

use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};
/**
 * Max Activities model.
 * Contains the max amount of activities an organ may create options for
 * Note that this is the limit per period!
 */
#[Entity]
class MaxActivities
{
    /**
     * ID for the field.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * Who created this activity.
     */
    #[ManyToOne(targetEntity: OrganModel::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected OrganModel $organ;

    /**
     * The value of the option.
     */
    #[Column(type: "integer")]
    protected int $value;

    /**
     * The associated period.
     */
    #[ManyToOne(targetEntity: ActivityOptionCreationPeriod::class)]
    protected ActivityOptionCreationPeriod $period;

    /**
     * @return ActivityOptionCreationPeriod
     */
    public function getPeriod(): ActivityOptionCreationPeriod
    {
        return $this->period;
    }

    /**
     * Set the period.
     *
     * @param ActivityOptionCreationPeriod $period
     */
    public function setPeriod(ActivityOptionCreationPeriod $period): void
    {
        $this->period = $period;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return OrganModel
     */
    public function getOrgan(): OrganModel
    {
        return $this->organ;
    }

    /**
     * Set the organ.
     *
     * @param OrganModel $organ
     */
    public function setOrgan(OrganModel $organ): void
    {
        $this->organ = $organ;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Set the value.
     *
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'organ' => $this->getOrgan(),
            'value' => $this->getValue(),
        ];
    }
}
