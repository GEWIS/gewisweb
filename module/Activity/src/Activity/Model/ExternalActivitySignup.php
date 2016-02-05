<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * ActivitySignup model.
 *
 * @ORM\Entity
 */
class ExternalActivitySignup extends ActivitySignup
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
     * Get the full name of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Get the email address of the user whom signed up for the activity.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
}
