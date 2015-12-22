<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;

/**
 * ActivitySignup model.
 *
 * @ORM\Entity
 */
class ActivitySignup
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
     * Get the signup id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * Get the activity which the user is signed up for.
     *
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }
}
