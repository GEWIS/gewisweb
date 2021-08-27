<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use User\Model\User;

/**
 * Signup model.
 *
 * @ORM\Entity
 */
class UserSignup extends Signup
{
    /**
     * Who is subscribed.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_lidnr", referencedColumnName="lidnr")
     */
    protected $user;

    /**
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string|null
     */
    public function getFullName()
    {
        if (is_null($this->user)) {
            return null;
        }
        $member = $this->user->getMember();
        if (is_null($member)) {
            return null;
        }
        return $this->getUser()->getMember()->getFullName();
    }

    /**
     * Get the user that is signed up.
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the user for the activity signup.
     */
    public function setUser(User $user)
    {
        $this->user = $user;
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
