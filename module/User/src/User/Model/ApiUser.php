<?php

namespace User\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Role\RoleInterface;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * User model.
 *
 * @ORM\Entity
 */
class ApiUser implements RoleInterface, ResourceInterface
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
     * Application name.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Authentication token.
     *
     * @ORM\Column(type="string")
     */
    protected $token;


    /**
     * Get the id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set the token.
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Get the API user's role ID.
     *
     * @return string
     */
    public function getRoleId()
    {
        return 'api_' . $this->getId();
    }

    /**
     * Get the API user's resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'api';
    }
}
