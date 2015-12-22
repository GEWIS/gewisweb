<?php

namespace Activity\Model;

use \DateTime;
use User\Model\User;

/**
 * Tranlated version of the Activity model. Populate with values from an
 * Activity\Model\Activity, to only expose one language. This model shoudl NOT
 * be preserved in the database.
 */
class ActivityTranslation
{
    /**
     * Status codes for the activity
     */
    const STATUS_TO_APPROVE = 1; // Activity needs to be approved
    const STATUS_APPROVED = 2;  // The activity is approved
    const STATUS_DISAPPROVED = 3; // The board disapproved the activity

    /**
     * ID for the activity.
     */
    protected $id;

    /**
     * Name for the activity.
     */
    protected $name;

    /**
     * The date and time the activity starts.
     */
    protected $beginTime;

    /**
     * The date and time the activity ends.
     */
    protected $endTime;

    /**
     * The location the activity is held at.
     */
    protected $location;

    /**
     * String to denote how much the activity costs
     */
    protected $costs;

    /**
     * Are people able to sign up for this activity?
     */
    protected $canSignUp;

    /**
     * Are people outside of GEWIS allowed to sign up
     * N.b. if $canSignUp is false, this column does not matter.
     */
    protected $onlyGEWIS;

    /**
     * Who did approve this activity.
     */
    protected $approver;

    /**
     * Who created this activity.
     */
    protected $creator;

    /**
     * What is the approval status      .
     */
    protected $status;

    /**
     * Activity description.
     */
    protected $description;

    /**
     * all the people who signed up for this activity
     */
    protected $signUps;

    /**
     * All additional fields belonging to the activity.
     */
    protected $fields;

    // TODO -> where can i find member organ?
    protected $organ;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return DateTime
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * @param DateTime $beginTime
     */
    public function setBeginTime($beginTime)
    {
        $this->beginTime = $beginTime;
    }

    /**
     * @return DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param DateTime $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
            $this->location = $location;
    }

    /**
     * @return string
     */
    public function getCosts()
    {
        return $this->costs;
    }

    /**
     * @param string $costs
     */
    public function setCosts($costs)
    {
        $this->costs = $costs;
    }

    /**
     * @return boolean
     */
    public function getCanSignUp()
    {
        return $this->canSignUp;
    }

    /**
     * @param boolean $canSignUp
     */
    public function setCanSignUp($canSignUp)
    {
        $this->canSignUp = $canSignUp;
    }

    /**
     * @return boolean
     */
    public function getOnlyGEWIS()
    {
        return $this->onlyGEWIS;
    }

    /**
     * @param boolean $onlyGEWIS
     */
    public function setOnlyGEWIS($onlyGEWIS)
    {
        $this->onlyGEWIS = $onlyGEWIS;
    }

    /**
     * @return User
     */
    public function getApprover()
    {
        return $this->approver;
    }

    /**
     * @param User $approver
     */
    public function setApprover(User $approver)
    {
        $this->approver = $approver;
    }

    /**
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param User $creator
     */
    public function setCreator(User $creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param integer $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getSignUps()
    {
        return $this->signUps;
    }

    /**
     * @param array $signUps
     */
    public function setSignUps($signUps)
    {
        $this->signUps = $signUps;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }
    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $fields = $this->getFields()->count();

        $attendees = [];
        foreach ($this->getSignUps() as $signup) {
            $attendees[] = $signup->getUser()->getMember()->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'beginTime' => $this->getBeginTime(),
            'endTime' => $this->getEndTime(),
            'location' => $this->getLocation(),
            'costs' => $this->getCosts(),
            'description' => $this->getDescription(),
            'attendees' => $attendees,
            'fields' => $fields,
        ];
    }
}


