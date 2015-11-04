<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class Activity
{
    /**
     * Status codes for the activity
     */
    const STATUS_TO_APPROVE = 1; // Activity needs to be approved
    const STATUS_APPROVED = 2;  // The activity is approved
    const STATUS_DISAPPROVED = 3; // The board disapproved the activity

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
     * @Orm\Column(type="string")
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
     * @ORM\Column(type="string")
     */
    protected $location;

    /**
     * How much does it cost. 0 = free!
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $costs;

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
     * Who did approve this activity.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $approver;

    /**
     * Who created this activity.
     *
     * @ORM\Column(nullable=false)
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(referencedColumnName="lidnr")
     */
    protected $creator;

    /**
     * What is the approval status      .
     *
     * @ORM\Column(type="integer")
     */
    protected $status;

    /**
     * Activity description.
     *
     * @Orm\Column(type="text")
     */
    protected $description;

    /**
     * all the signups for this activity.
     *
     * @ORM\OneToMany(targetEntity="ActivitySignup", mappedBy="activity")
     */
    protected $signUps;

    /**
     * All additional fields belonging to the activity.
     * 
     * @ORM\OneToMany(targetEntity="ActivityField", mappedBy="activity")
     */
    protected $fields;
    
    // TODO -> where can i find member organ?
    protected $organ;

    /**
     * Set the approval status of the activity
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        if (!in_array($status, [static::STATUS_TO_APPROVE, static::STATUS_APPROVED, static::STATUS_DISAPPROVED])) {
            throw new \InvalidArgumentException('No such status ' . $status);
        }
        $this->status = $status;
    }

    /**
     * Get the status of the activity
     *
     * @return int $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the status of a variable
     *
     * @param $variable
     * @return mixed
     */
    public function get($variable)
    {
        return $this->$variable;
    }

    /**
     * Get a string format of the costs. Either a number or unknown
     * @return string
     */
    public function getCostString()
    {
        return is_null($this->costs) ? 'Unknown' : $this->costs;
    }

    /**
     * Create a new activity.
     *
     * @param array $params Parameters for the new activity
     * @param EntitiyManager $em The relevant entity manager.
     * 
     * @throws \Exception If a activity is loaded
     * @throws \Exception If a necessary parameter is not set
     *
     * @return \Activity\Model\Activity the created activity
     */
    public function create(array $params, $em)
    {
        if ($this->id != null) {
            throw new \Exception('There is already a loaded activity');
        }
        foreach (['description', 'name', 'beginTime', 'endTime', 'costs', 'location', 'creator', 'canSignUp'] as $param) {
            if (!isset($params[$param])) {
                throw new \Exception("create: parameter $param not set");
            }
            $this->$param = $params[$param];
        }

        // If the costs are not yet known, set them to null
        if (isset($params['costs_unknown']) && $params['costs_unknown'] == 1) {
            $this->costs = null;
        }

        /** @var $user User*/
        $user = $params['creator'];

        $this->beginTime = new \DateTime($this->beginTime);
        $this->endTime = new \DateTime($this->endTime);
        $this->creator = $user->getLidNr();

        // TODO: These values need to be set correctly
        $this->onlyGEWIS = true;
        $this->approved = 0;
        $this->status = static::STATUS_TO_APPROVE;        
        if (isset($params['fields'])) {
            foreach ($params['fields'] as $fieldparams){
                
                $field = new ActivityField();
                $field->create($fieldparams, $this, $em);
                $em->persist($field);
            }
            $em->flush();
        }
        return $this;
    }

    /**
     * Returns if an user can sign up for this activity.
     */
    public function canSignUp()
    {
        return $this->canSignUp;
    }
}
