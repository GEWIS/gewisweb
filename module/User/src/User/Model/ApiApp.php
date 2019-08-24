<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * ApiApp model
 *
 * @ORM\Entity
 */
class ApiApp
{
    /**
     * Id
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Application ID
     *
     * @ORM\Column(type="string")
     */
    protected $appId;

    /**
     * Application secret
     *
     * @ORM\Column(type="string")
     */
    protected $secret;

    /**
     * Callback URL
     *
     * @ORM\Column(type="string")
     */
    protected $callback;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getCallback()
    {
        return $this->callback;
    }
}