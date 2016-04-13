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
     * The PHPSESSID of this session
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $id;

    /**
     * The user whom created this session.
     *
     * @ORM\ManyToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="user_id",referencedColumnName="lidnr")
     */
    protected $user;

    /**
     * The ip the session was created at.
     *
     * @ORM\Column(type="string")
     */
    protected $ip;

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
