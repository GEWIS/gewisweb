<?php

namespace Activity\Model;

use DateTime;
use Decision\Model\Organ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use User\Model\User;
use User\Permissions\Resource\CreatorResourceInterface;
use User\Permissions\Resource\OrganResourceInterface;

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
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $name;

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
     * The location the activity is held at.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $location;

    /**
     * How much does it cost.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $costs;

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
     * @ORM\JoinColumn(referencedColumnName="lidnr", nullable=false)
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
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist", "remove"})
     */
    protected $description;

    /**
     * All additional Categories belonging to this activity.
     *
     * @ORM\ManyToMany(targetEntity="Activity\Model\ActivityCategory", inversedBy="activities", cascade={"persist"})
     * @ORM\JoinTable(name="ActivityCategoryAssignment")
     */
    protected $categories;

    /**
     * All additional SignupLists belonging to this activity.
     *
     * @ORM\OneToMany(targetEntity="Activity\Model\SignupList", mappedBy="activity", cascade={"remove"})
     */
    protected $signupLists;

    /**
     * Which organ organises this activity.
     *
     * @ORM\ManyToOne(targetEntity="Decision\Model\Organ")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    protected $organ;

    /**
     * Which company organises this activity.
     *
     * @ORM\ManyToOne(targetEntity="Company\Model\Company")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    protected $company;

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

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->signupLists = new ArrayCollection();
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
     * @param array $categories
     */
    public function addCategories($categories)
    {
        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    /**
     * @param ActivityCategory $category
     */
    public function addCategory($category)
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories->add($category);
        $category->addActivity($this);
    }

    /**
     * @param array $categories
     */
    public function removeCategories($categories)
    {
        foreach ($categories as $category) {
            $this->removeCategory($category);
        }
    }

    /**
     * @param ActivityCategory $category
     */
    public function removeCategory($category)
    {
        if (!$this->categories->contains($category)) {
            return;
        }

        $this->categories->removeElement($category);
        $category->removeActivity($this);
    }

    /**
     * Adds SignupLists to this activity.
     *
     * @param array $signupLists
     */
    public function addSignupLists($signupLists)
    {
        foreach ($signupLists as $signupList) {
            $this->addSignupList($signupList);
        }
    }

    /**
     * @param SignupList $signupList
     */
    public function addSignupList($signupList)
    {
        if ($this->signupLists->contains($signupList)) {
            return;
        }

        $this->signupLists->add($signupList);
        $signupList->setActivity($this);
    }

    /**
     * Removes SignupLists from this activity.
     *
     * @param array $signupLists
     */
    public function removeSignupLists($signupLists)
    {
        foreach ($signupLists as $signupList) {
            $this->removeSignupList($signupList);
        }
    }

    /**
     * @param SignupList $category
     */
    public function removeSignupList($signupList)
    {
        if (!$this->signupLists->contains($signupList)) {
            return;
        }

        $this->signupLists->removeElement($signupList);
        $signupList->setActivity(null);
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $signupLists = [];
        foreach ($this->getSignupLists() as $signupList) {
            $signupLists[] = $signupList->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'beginTime' => $this->getBeginTime(),
            'endTime' => $this->getEndTime(),
            'location' => $this->getLocation()->getValueNL(),
            'locationEn' => $this->getLocation()->getValueEN(),
            'costs' => $this->getCosts()->getValueNL(),
            'costsEn' => $this->getCosts()->getValueEN(),
            'description' => $this->getDescription()->getValueNL(),
            'descriptionEn' => $this->getDescription()->getValueEN(),
            'organ' => $this->getOrgan(),
            'company' => $this->getCompany(),
            'isMyFuture' => $this->getIsMyFuture(),
            'requireGEFLITST' => $this->getRequireGEFLITST(),
            'categories' => $this->getCategories()->toArray(),
            'signupLists' => $signupLists,
        ];
    }

    /**
     * Returns an ArrayCollection of SignupLists associated with this activity.
     *
     * @return ArrayCollection
     */
    public function getSignupLists()
    {
        return $this->signupLists;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return LocalisedText
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param LocalisedText $location
     */
    public function setLocation($location)
    {
        $this->location = $location->copy();
    }

    /**
     * @return LocalisedText
     */
    public function getCosts()
    {
        return $this->costs;
    }

    /**
     * @param LocalisedText $costs
     */
    public function setCosts($costs)
    {
        $this->costs = $costs->copy();
    }

    /**
     * @return LocalisedText
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param LocalisedText $description
     */
    public function setDescription($description)
    {
        $this->description = $description->copy();
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
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $organ
     */
    public function setCompany($company)
    {
        $this->company = $company;
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

    /**
     * @return ArrayCollection
     */
    public function getCategories()
    {
        return $this->categories;
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
     * Get the organ of this resource.
     *
     * @return Organ
     */
    public function getResourceOrgan()
    {
        return $this->getOrgan();
    }

    /**
     * Get the creator of this resource
     *
     * @return User
     */
    public function getResourceCreator()
    {
        return $this->getCreator();
    }

    // Permission to link the resource to an organ

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
}
