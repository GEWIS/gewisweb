<?php

namespace Activity\Model;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Activity Options Creation Period
 * Contains a period during which options may be created
 *
 * @ORM\Entity
 */
class ActivityOptionCreationPeriod
{
    /**
     * ID for the field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    /**
     * The date and time the planning period starts.
     *
     * @ORM\Column(type="datetime")
     */
    protected $beginPlanningTime;
    /**
     * The date and time the planning period ends.
     *
     * @ORM\Column(type="datetime")
     */
    protected $endPlanningTime;
    /**
     * The date and time the period for which options can be created starts.
     *
     * @ORM\Column(type="datetime")
     */
    protected $beginOptionTime;
    /**
     * The date and time the period for which options can be created ends.
     *
     * @ORM\Column(type="datetime")
     */
    protected $endOptionTime;

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'beginPlanningTime' => $this->getBeginPlanningTime(),
            'endPlanningTime' => $this->getEndPlanningTime(),
            'beginOptionTime' => $this->getBeginOptionTime(),
            'endOptionTime' => $this->getEndOptionTime(),
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getBeginPlanningTime()
    {
        return $this->beginPlanningTime;
    }

    /**
     * @param DateTime $beginPlanningTime
     */
    public function setBeginPlanningTime($beginPlanningTime)
    {
        $this->beginPlanningTime = $beginPlanningTime;
    }

    /**
     * @return DateTime
     */
    public function getEndPlanningTime()
    {
        return $this->endPlanningTime;
    }

    /**
     * @param DateTime $endPlanningTime
     */
    public function setEndPlanningTime($endPlanningTime)
    {
        $this->endPlanningTime = $endPlanningTime;
    }

    /**
     * @return DateTime
     */
    public function getBeginOptionTime()
    {
        return $this->beginOptionTime;
    }

    /**
     * @param DateTime $beginOptionTime
     */
    public function setBeginOptionTime($beginOptionTime)
    {
        $this->beginOptionTime = $beginOptionTime;
    }

    /**
     * @return DateTime
     */
    public function getEndOptionTime()
    {
        return $this->endOptionTime;
    }

    /**
     * @param DateTime $endTime
     */
    public function setEndOptionTime($endOptionTime)
    {
        $this->endOptionTime = $endOptionTime;
    }
}
