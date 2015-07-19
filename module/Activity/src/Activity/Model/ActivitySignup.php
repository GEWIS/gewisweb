<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class ActivitySignup
{
    /**
     * ID for the activity.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Album in which the photo is.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="signUps")
     * @ORM\JoinColumn(name="activity_id",referencedColumnName="id")
     */
    protected $activity;

    /**
     * Who is subscribed.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_lidnr", referencedColumnName="lidnr")
     */
    protected $user;

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
     * Set the user for the activity signup.
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get the user that is signed up.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
