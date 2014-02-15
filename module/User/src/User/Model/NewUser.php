<?php

namespace User\Model;

use Decision\Model\Member;

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
     * Member's name
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The user's activation code.
     *
     * @ORM\Column(type="string")
     */
    protected $code;


    /**
     * Constructor.
     *
     * We can populate most values from a member model.
     *
     * @param Member $member
     */
    public function __construct(Member $member = null)
    {
        if (null !== $member) {
            $this->lidnr = $member->getLidnr();
            $this->email = $member->getEmail();
            $this->name = $member->getFirstName() . ' ' . $member->getLastName();
        }
    }

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
     * Get the member's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the user's membership number.
     *
     * @param int $lidnr
     */
    public function setLidnr($lidnr)
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the user's email address
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
