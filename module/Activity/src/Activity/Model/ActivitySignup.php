<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;

/**
 * ActivitySignup model.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({"user"="UserActivitySignup","external"="ExternalActivitySignup"})
 */
abstract class ActivitySignup
{
    /**
     * ID for the signup.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The activity the signup is for.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="signUps", onDelete="CASCADE")
     * @ORM\JoinColumn(name="activity_id",referencedColumnName="id")
     */
    protected $activity;

    /**
     * All the extra field values
     * @ORM\OneToMany(targetEntity="ActivityFieldValue", mappedBy="signup", cascade={"persist", "remove"})
     */
    protected $fieldValues;

    /**
     * Set the activity that the user signed up for.
     *
     * @param Activity $activity
     */
    public function setActivity(Activity $activity)
    {
        $this->activity = $activity;
    }

    /**
     * Get the signup id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the activity which the user is signed up for.
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Get all the extra field values.
     *
     * @return array
     */
    public function getFieldValues()
    {
        return $this->fieldValues;
    }

    /**
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string
     */
    abstract public function getFullName();

    /**
     * Get the email address of the user whom signed up for the activity.
     *
     * @return string
     */
    abstract public function getEmail();
}
