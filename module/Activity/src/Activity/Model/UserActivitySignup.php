<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use \User\Model\User;

/**
 * ActivitySignup model.
 *
 * @ORM\Entity
 */
class UserActivitySignup extends ActivitySignup
{
    /**
     * Who is subscribed.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_lidnr", referencedColumnName="lidnr")
     */
    protected $user;

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

    /**
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getFullName()
    {
        return is_null($this->getUser()) ? null : $this->getUser()->getMember()->getFullName();
    }

    /**
     * Get the email address of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getEmail()
    {
        return is_null($this->getUser()) ? null : $this->getUser()->getMember()->getEmail();
    }
}