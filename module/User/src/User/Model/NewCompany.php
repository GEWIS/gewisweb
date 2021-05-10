<?php

namespace User\Model;

use DateTime;
use Decision\Model\Member;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * User model.
 *
 * @ORM\Entity
 */
class NewCompany
{
    /**
     * The membership number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The user's email address.
     *
     * @ORM\Column(type="string")
     */
    protected $contactEmail;

    /**
     * The user's activation code.
     *
     * @ORM\Column(type="string")
     */
    protected $code;

    /**
     * Registration attempt timestamp
     *
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $time;

    /**
     * Get the company id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the user's email address.
     *
     * @return string
     */
    public function getContactEmail()
    {
        return $this->contactEmail;
    }

    /**
     * Get the activation code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the registration time.
     *
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set the activation code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Set the user's email address
     *
     * @param string $contactEmail
     */
    public function setContactEmail($contactEmail)
    {
        $this->contactEmail = $contactEmail;
    }
}
