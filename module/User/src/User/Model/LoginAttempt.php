<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * A failed login attempt
 *
 * @ORM\Entity
 */
class LoginAttempt
{
    const TYPE_PIN = 'pin';
    const TYPE_NORMAL = 'normal';

    /**
     * Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The user for which the login was attempted.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="lidnr")
     */
    protected $user;

    /**
     * The ip from which the login was attempted
     *
     * @ORM\Column(type="string")
     */
    protected $ip;

    /**
     * Type of login {pin,normal}
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Attempt timestamp.
     *
     * @ORM\Column(type="datetime")
     */
    protected $time;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \User\Model\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param \User\Model\User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return \DateTime
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @param \DateTime $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }
}
