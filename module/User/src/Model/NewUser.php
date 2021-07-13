<?php

namespace User\Model;

use DateTime;
use Decision\Model\Member;
use Doctrine\ORM\Mapping as ORM;

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
     * User's member.
     *
     * @ORM\OneToOne(targetEntity="Decision\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Registration attempt timestamp.
     *
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $time;

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
            $this->member = $member;
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
     * Get the registration time.
     *
     * @return DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Get the member.
     *
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
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
     * Set the user's email address.
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

    /**
     * Set the registration time.
     *
     * @param DateTime $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }
}
