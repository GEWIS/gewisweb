<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use \DateTime;
use User\Permissions\Resource\OrganResourceInterface;
use User\Permissions\Resource\CreatorResourceInterface;

/**
 * SignupList model.
 *
 * @ORM\Entity
 */
class SignupList implements OrganResourceInterface, CreatorResourceInterface
{
    /**
     * ID for the SignupList.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The Activity this SignupList belongs to.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="signupLists", cascade={"persist"})
     * @ORM\JoinColumn(name="activity_id", referencedColumnName="id")
     */
    protected $activity;

    /**
     * The name of the SignupList.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist"})
     */
    protected $name;

    /**
     * The date and time the SignupList is open for signups.
     *
     * @ORM\Column(type="datetime")
     */
    protected $openDate;

    /**
     * The date and time after which the SignupList is no longer open.
     *
     * @ORM\Column(type="datetime")
     */
    protected $closeDate;

    /**
     * Determines if people outside of GEWIS can sign up.
     *
     * @ORM\Column(type="boolean")
     */
    protected $onlyGEWIS;

    /**
     * Determines if the number of signed up members should be displayed
     * when the user is NOT logged in.
     *
     * @ORM\Column(type="boolean")
     */
    protected $displaySubscribedNumber;

    /**
     * All additional fields belonging to the activity.
     *
     * @ORM\OneToMany(targetEntity="SignupField", mappedBy="signupList", orphanRemoval=true)
     */
    protected $fields;

    /**
     * All the people who signed up for this SignupList.
     *
     * @ORM\OneToMany(targetEntity="Signup", mappedBy="signupList", orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $signUps;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the associated Activity.
     *
     * @return \Activity\Model\Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Sets the associated Activity.
     *
     * @param \Activity\Model\Activity $activity
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
    }

    /**
     * @return \Activity\Model\LocalisedText
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Activity\Model\LocalisedText $name
     */
    public function setName($name)
    {
        $this->name = $name->copy();
    }

    /**
     * Returns the opening DateTime of this SignupList.
     *
     * @return DateTime
     */
    public function getOpenDate()
    {
        return $this->openDate;
    }

    /**
     * Sets the opening DateTime of this SignupList.
     *
     * @param DateTime $openDate
     */
    public function setOpenDate($openDate)
    {
        $this->openDate = $openDate;
    }

    /**
     * Returns the closing DateTime of this SignupList.
     *
     * @return DateTime
     */
    public function getCloseDate()
    {
        return $this->closeDate;
    }

    /**
     * Sets the closing DateTime of this SignupList.
     *
     * @param DateTime $closeDate
     */
    public function setCloseDate($closeDate)
    {
        $this->closeDate = $closeDate;
    }

    /**
     * Returns true if this SignupList is only available to members of GEWIS.
     *
     * @return boolean
     */
    public function getOnlyGEWIS()
    {
        return $this->onlyGEWIS;
    }

    /**
     * Sets whether or not this SignupList is available to members of GEWIS.
     *
     * @param boolean $onlyGEWIS
     */
    public function setOnlyGEWIS($onlyGEWIS)
    {
        $this->onlyGEWIS = $onlyGEWIS;
    }

    /**
     * Returns true if this SignupList shows the number of members who signed up
     * when the user is not logged in.
     *
     * @return boolean
     */
    public function getDisplaySubscribedNumber()
    {
        return $this->displaySubscribedNumber;
    }

    /**
     * Sets whether or not this SignupList should show the number of members who
     * signed up when the user is not logged in.
     *
     * @param boolean $displaySubscribedNumber
     */
    public function setDisplaySubscribedNumber($displaySubscribedNumber)
    {
        $this->displaySubscribedNumber = $displaySubscribedNumber;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param array $signUps
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
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

    public function toArray()
    {
        $fields = [];
        foreach ($this->getFields() as $field) {
            $fields[] = $field->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'openDate' => $this->getOpenDate(),
            'closeDate' => $this->getCloseDate(),
            'onlyGEWIS' => $this->getOnlyGEWIS(),
            'displaySubscribedNumber' => $this->getDisplaySubscribedNumber(),
            'fields' => $fields,
        ];
    }

    /**
     * Returns the string identifier of the Resource
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'signupList';
    }

    // Permission to link the resource to an organ
    /**
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan()
    {
        return $this->getActivity()->getOrgan();
    }

    /**
     * Get the creator of this resource
     *
     * @return User
     */
    public function getResourceCreator()
    {
        return $this->getActivity()->getCreator();
    }
}
