<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\IdentityInterface;
use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * User model.
 */
#[Entity]
class ApiUser implements IdentityInterface
{
    use IdentifiableTrait;

    /**
     * Application name.
     */
    #[Column(type: 'string')]
    protected string $name;

    /**
     * Authentication token.
     */
    #[Column(type: 'string')]
    protected string $token;

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Set the token.
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get the API user's role ID.
     */
    public function getRoleId(): string
    {
        return 'apiuser';
    }

    /**
     * Get the API user's resource ID.
     */
    public function getResourceId(): string
    {
        return 'api';
    }
}
