<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM,
    Doctrine\Common\Collections\ArrayCollection;
use Zend\Crypt\Password\Bcrypt as Bcrypt;

/**
 * User model.
 *
 * @ORM\Entity
 */
class User
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
     * The user's password.
     *
     * @ORM\Column(type="string")
     */
    protected $password;


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
     * Get the password hash.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
