<?php

namespace User\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id
};
use Laminas\Permissions\Acl\{
    Resource\ResourceInterface,
    Role\RoleInterface,
};

/**
 * User model.
 */
#[Entity]
class ApiUser implements RoleInterface, ResourceInterface
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Application name.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * Authentication token.
     */
    #[Column(type: "string")]
    protected string $token;

    /**
     * Get the id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the token.
     *
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get the API user's role ID.
     *
     * @return string
     */
    public function getRoleId(): string
    {
        return 'apiuser';
    }

    /**
     * Get the API user's resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'api';
    }
}
