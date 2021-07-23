<?php

namespace User\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};

/**
 * ApiApp model.
 */
#[Entity]
class ApiApp
{
    /**
     * Id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * Application ID.
     */
    #[Column(type: "string")]
    protected string $appId;

    /**
     * Application secret.
     */
    #[Column(type: "string")]
    protected string $secret;

    /**
     * Callback URL.
     */
    #[Column(type: "string")]
    protected string $callback;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getCallback(): string
    {
        return $this->callback;
    }
}
