<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity Category model.
 *
 * @ORM\Entity
 */
class ActivityCategory
{
    /**
     * Id for the Category.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The Activities this Category belongs to.
     *
     * @ORM\ManyToMany(targetEntity="Activity\Model\Activity", mappedBy="categories", cascade={"persist"})
     */
    protected $activities;

    /**
     * Name for the Category.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $name;

    public function __construct()
    {
        $this->activities = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getActivities()
    {
        return $this->activities->toArray();
    }

    /**
     * @param \Activity\Model\Activity $activity
     */
    public function addActivity($activity)
    {
        $this->activities->add($activity);
    }

    /**
     * @param \Activity\Model\Activity $activity
     */
    public function removeActivity($activity)
    {
        $this->activities->removeElement($activity);
    }

    /**
     * @return LocalisedText
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param LocalisedText $name
     */
    public function setName($name)
    {
        $this->name = $name->copy();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'activities' => $this->getActivities(),
        ];
    }
}
