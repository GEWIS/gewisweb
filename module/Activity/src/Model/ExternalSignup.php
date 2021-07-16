<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExternalSignup model.
 *
 * @ORM\Entity
 */
class ExternalSignup extends Signup
{
    /**
     * The full name of the external subscriber.
     *
     * @ORM\Column(type="string")
     */
    protected $fullName;

    /**
     * The email address of the external subscriber.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * Gets the full name of the user who signed up for the activity.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Sets the full name of the user who signed up for the activity.
     *
     * @param string $fullName
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * Get the email address of the user who signed up for the activity.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Sets the e-mail address of the user who signed up for the activity.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
