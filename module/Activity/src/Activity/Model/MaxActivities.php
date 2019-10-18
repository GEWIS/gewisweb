<?php

namespace Activity\Model;

use Decision\Model\Organ;
use Doctrine\ORM\Mapping as ORM;

/**
 * Max Activities model.
 * Contains the max amount of activities an organ may create options for
 * Note that this is the limit per period!
 *
 * @ORM\Entity
 */
class MaxActivities
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
     * Who created this activity.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Organ")
     * @ORM\JoinColumn(referencedColumnName="id",nullable=false)
     */
    protected $organ;
    /**
     * The value of the option.
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $value;
    /**
     * The associated period
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\ActivityOptionCreationPeriod")
     */
    protected $period;

    /**
     * @return ActivityOptionCreationPeriod
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * Set the period
     *
     * @param ActivityOptionCreationPeriod $period
     */
    public function setPeriod($period)
    {
        $this->period = $period;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'organ' => $this->getOrgan(),
            'value' => $this->getValue()
        ];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Organ
     */
    public function getOrgan()
    {
        return $this->organ;
    }

    /**
     * Set the organ
     *
     * @param Organ $organ
     */
    public function setOrgan($organ)
    {
        $this->organ = $organ;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value
     *
     * @param int $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
