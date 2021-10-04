<?php

namespace Activity\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};

/**
 * Activity Options Creation Period
 * Contains a period during which options may be created.
 */
#[Entity]
class ActivityOptionCreationPeriod
{
    /**
     * ID for the field.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * The date and time the planning period starts.
     */
    #[Column(type: "datetime")]
    protected DateTime $beginPlanningTime;

    /**
     * The date and time the planning period ends.
     */
    #[Column(type: "datetime")]
    protected DateTime $endPlanningTime;

    /**
     * The date and time the period for which options can be created starts.
     */
    #[Column(type: "datetime")]
    protected DateTime $beginOptionTime;

    /**
     * The date and time the period for which options can be created ends.
     */
    #[Column(type: "datetime")]
    protected DateTime $endOptionTime;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getBeginPlanningTime(): DateTime
    {
        return $this->beginPlanningTime;
    }

    /**
     * @param DateTime $beginPlanningTime
     */
    public function setBeginPlanningTime(DateTime $beginPlanningTime): void
    {
        $this->beginPlanningTime = $beginPlanningTime;
    }

    /**
     * @return DateTime
     */
    public function getEndPlanningTime(): DateTime
    {
        return $this->endPlanningTime;
    }

    /**
     * @param DateTime $endPlanningTime
     */
    public function setEndPlanningTime(DateTime $endPlanningTime): void
    {
        $this->endPlanningTime = $endPlanningTime;
    }

    /**
     * @return DateTime
     */
    public function getBeginOptionTime(): DateTime
    {
        return $this->beginOptionTime;
    }

    /**
     * @param DateTime $beginOptionTime
     */
    public function setBeginOptionTime(DateTime $beginOptionTime): void
    {
        $this->beginOptionTime = $beginOptionTime;
    }

    /**
     * @return DateTime
     */
    public function getEndOptionTime(): DateTime
    {
        return $this->endOptionTime;
    }

    /**
     * @param DateTime $endOptionTime
     */
    public function setEndOptionTime(DateTime $endOptionTime): void
    {
        $this->endOptionTime = $endOptionTime;
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
            'beginPlanningTime' => $this->getBeginPlanningTime(),
            'endPlanningTime' => $this->getEndPlanningTime(),
            'beginOptionTime' => $this->getBeginOptionTime(),
            'endOptionTime' => $this->getEndOptionTime(),
        ];
    }
}
