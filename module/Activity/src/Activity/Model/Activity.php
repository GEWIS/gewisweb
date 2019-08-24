<?php

namespace Activity\Model;

use Decision\Model\Organ;
use Doctrine\ORM\Mapping as ORM;
use \DateTime;
use User\Model\User;
use User\Permissions\Resource\OrganResourceInterface;
use User\Permissions\Resource\CreatorResourceInterface;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class Activity implements OrganResourceInterface, CreatorResourceInterface
{
    /**
     * Status codes for the activity
     */
    const STATUS_TO_APPROVE = 1; // Activity needs to be approved
    const STATUS_APPROVED = 2;  // The activity is approved
    const STATUS_DISAPPROVED = 3; // The board disapproved the activity
    const STATUS_UPDATE = 4; //This activity is an update for some activity

    /**
     * ID for the activity.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Name for the activity.
     *
     * @Orm\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * English name for the activity
     *
     * @Orm\Column(type="string", nullable=true)
     */
    protected $nameEn;

    /**
     * The date and time the activity starts.
     *
     * @ORM\Column(type="datetime")
     */
    protected $beginTime;

    /**
     * The date and time the activity ends.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $endTime;


    /**
     * The date and time the activity ends.
     *
     * @ORM\Column(type="datetime")
     */
    protected $subscriptionDeadline;

    /**
     * The location the activity is held at.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $location;

    /**
     * English string to denote what location the activity is held on
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locationEn;

    /**
     * How much does it cost.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $costs;

    /**
     * English string to denote how much the activity cost
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $costsEn;

    /**
     * Are people able to sign up for this activity?
     *
     * @ORM\Column(type="boolean")
     */
    protected $canSignUp;

    /**
     * Are people outside of GEWIS allowed to sign up
     * N.b. if $canSignUp is false, this column does not matter.
     *
     * @ORM\Column(type="boolean")
     */
    protected $onlyGEWIS;

    /**
     * Should the number of subscribed members be displayed
     * when the user is NOT logged in?
     *
     * @ORM\Column(type="boolean")
     */
    protected $displaySubscribedNumber;

    /**
     * Who did approve this activity.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $approver;

    /**
     * Who created this activity.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr",nullable=false)
     */
    protected $creator;

    /**
     * What is the approval status      .
     *
     * @ORM\Column(type="integer")
     */
    protected $status;

    /**
     * The update proposal associated with this activity
     *
     * @ORM\OneToMany(targetEntity="Activity\Model\ActivityUpdateProposal", mappedBy="old")
     */
    protected $updateProposal;

    /**
     * Activity description.
     *
     * @Orm\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * Activity description.
     *
     * @Orm\Column(type="text", nullable=true)
     */
    protected $descriptionEn;

    /**
     * all the people who signed up for this activity
     *
     * @ORM\OneToMany(targetEntity="ActivitySignup", mappedBy="activity")
     * @ORM\OrderBy({"id" = "ASC"})
     */
    protected $signUps;

    /**
     * All additional fields belonging to the activity.
     *
     * @ORM\OneToMany(targetEntity="ActivityField", mappedBy="activity")
     */
    protected $fields;

    /**
     * Who created this activity.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Organ")
     * @ORM\JoinColumn(referencedColumnName="id",nullable=true)
     */
    protected $organ;

    /**
     * Is this a My Future related activity
     *
     * @ORM\Column(type="boolean")
     */
    protected $isMyFuture;

    /**
     * Whether this activity needs a GEFLITST photographer
     *
     * @ORM\Column(type="boolean")
     */
    protected $requireGEFLITST;

    /**
     * Is this a food subscription list
     *
     * @ORM\Column(type="boolean")
     */
    protected $isFood;

    public function __construct()
    {
        $this->fields = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @return string
     */
    public function getNameEn()
    {
        return $this->nameEn;
    }

    /**
     * @param string $nameEn
     */
    public function setNameEn($nameEn)
    {
        $this->nameEn = $nameEn;
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
     * @return mixed
     */
    public function getSubscriptionDeadline()
    {
        return $this->subscriptionDeadline;
    }

    /**
     * @param mixed $subscriptionDeadline
     */
    public function setSubscriptionDeadline($subscriptionDeadline)
    {
        $this->subscriptionDeadline = $subscriptionDeadline;
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
        {
            $this->location = $location;
        }
    }

    /**
     * @return string
     */
    public function getLocationEn()
    {
        return $this->locationEn;
    }

    /**
     * @param string $locationEn
     */
    public function setLocationEn($locationEn)
    {
        $this->locationEn = $locationEn;
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
     * @return string
     */
    public function getCostsEn()
    {
        return $this->costsEn;
    }

    /**
     * @param string $costsEn
     */
    public function setCostsEn($costsEn)
    {
        $this->costsEn = $costsEn;
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
     * @return boolean
     */
    public function getDisplaySubscribedNumber()
    {
        return $this->displaySubscribedNumber;
    }

    /**
     * @param boolean $displaySubscribedNumber
     */
    public function setDisplaySubscribedNumber($displaySubscribedNumber)
    {
        $this->displaySubscribedNumber = $displaySubscribedNumber;
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
     * @return Activity\Model\ActivityUpdateProposal
     */
    public function getUpdateProposal()
    {
        return $this->updateProposal;
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
     * @return string
     */
    public function getDescriptionEn()
    {
        return $this->descriptionEn;
    }

    /**
     * @param string $descriptionEn
     */
    public function setDescriptionEn($descriptionEn)
    {
        $this->descriptionEn = $descriptionEn;
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
     * @return mixed
     */
    public function getOrgan()
    {
        return $this->organ;
    }

    /**
     * @param mixed $organ
     */
    public function setOrgan($organ)
    {
        $this->organ = $organ;
    }

    /**
     * @return mixed
     */
    public function getIsMyFuture()
    {
        return $this->isMyFuture;
    }

    /**
     * @param mixed $isMyFuture
     */
    public function setIsMyFuture($isMyFuture)
    {
        $this->isMyFuture = $isMyFuture;
    }

    /**
     * @return mixed
     */
    public function getIsFood()
    {
        return $this->isFood;
    }

    /**
     * @param mixed $isFood
     */
    public function setIsFood($isFood)
    {
        $this->isFood = $isFood;
    }


    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function removeFields($fields)
    {
        foreach ($fields as $field) {
            $field->setActivity(null);
            $this->fields->removeElement($field);
        }
    }

    public function addFields($fields)
    {
        foreach ($fields as $field) {
            $field->setActivity($this);
            $this->fields->add($field);
        }
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $fields = [];
        foreach ($this->getFields() as $field) {
            $fields[] = $field->toArray();
        }

        $attendees = [];
        foreach ($this->getSignUps() as $signup) {
            $attendees[] = $signup->getFullName();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'nameEn' => $this->getNameEn(),
            'beginTime' => $this->getBeginTime(),
            'endTime' => $this->getEndTime(),
            'subscriptionDeadline' => $this->getSubscriptionDeadline(),
            'location' => $this->getLocation(),
            'LocationEn' => $this->getLocationEn(),
            'costs' => $this->getCosts(),
            'costsEn' => $this->getCostsEn(),
            'description' => $this->getDescription(),
            'descriptionEn' => $this->getDescriptionEn(),
            'canSignUp' => $this->getCanSignUp(),
            'isFood' => $this->getIsFood(),
            'isMyFuture' => $this->getIsMyFuture(),
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'displaySubscribedNumber' => $this->getDisplaySubscribedNumber(),
            'attendees' => $attendees,
            'fields' => $fields,
        ];
    }

    // Permission to link the resource to an organ
    /**
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan()
    {
        return $this->getOrgan();
    }

    /**
     * Returns the string identifier of the Resource
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'activity';
    }

    /**
     * Get the creator of this resource
     */
    public function getResourceCreator()
    {
        return $this->getCreator();
    }

    /**
     * @return mixed
     */
    public function getRequireGEFLITST()
    {
        return $this->requireGEFLITST;
    }

    /**
     * @param mixed $requireGEFLITST
     */
    public function setRequireGEFLITST($requireGEFLITST)
    {
        $this->requireGEFLITST = $requireGEFLITST;
    }
}
