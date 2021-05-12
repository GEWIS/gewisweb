<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * A session of a user. Storing this allows session to be easily destroyed if needed.
 *
 * @ORM\Entity
 */
class Session
{
    /**
     * The id of this session
     *
     * @ORM\Id
     * @ORM\Column(type="guid")
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * The user whom created this session.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User", inversedBy="sessions")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="lidnr")
     */
    protected $user;

    /**
     * The company whom created this session.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\CompanyUser", inversedBy="sessions")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="id")
     */
    protected $company;

    /**
     * The ip the session was created at.
     *
     * @ORM\Column(type="string")
     */
    protected $ip;

    /**
     * A cryptographically secure random session secret.
     *
     * @ORM\Column(type="string")
     */
    protected $secret;

    /**
     * @return mixed
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param mixed $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return mixed
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param mixed $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * The time the session was created at.
     *
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * The last time this session was active
     *
     * @ORM\Column(type="datetime")
     */
    protected $lastActive;

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getLastActive()
    {
        return $this->lastActive;
    }

    /**
     * @param mixed $lastActive
     */
    public function setLastActive($lastActive)
    {
        $this->lastActive = $lastActive;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
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
}
