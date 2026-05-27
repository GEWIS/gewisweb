<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * User model.
 */
#[Entity]
class ApiUser
{
    use IdentifiableTrait;

    /**
     * Application name.
     */
    #[Column(type: Types::STRING)]
    private string $name;

    /**
     * Authentication token.
     */
    #[Column(type: Types::STRING)]
    private string $token;

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
     * Get the API user's resource ID.
     */
    public function getResourceId(): string
    {
        return 'api';
    }
}
