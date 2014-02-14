<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * User model.
 *
 * @ORM\Entity
 */
class NewUser
{

    /**
     * The membership number.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $lidnr;

    /**
     * The user's email address.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * The user's activation code.
     *
     * @ORM\Column(type="string")
     */
    protected $code;


    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->lidnr;
    }

    /**
     * Get the user's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Set the activation code.
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }
}
