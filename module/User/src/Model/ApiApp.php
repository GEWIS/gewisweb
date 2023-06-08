<?php

declare(strict_types=1);

namespace User\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use User\Model\Enums\JWTClaims;

/**
 * ApiApp model.
 */
#[Entity]
class ApiApp
{
    use IdentifiableTrait;

    /**
     * Application ID.
     */
    #[Column(type: 'string')]
    protected string $appId;

    /**
     * Application secret.
     */
    #[Column(type: 'string')]
    protected string $secret;

    /**
     * Callback URL.
     */
    #[Column(type: 'string')]
    protected string $callback;

    /**
     * URL for the application when the user does not authorise access.
     */
    #[Column(type: 'string')]
    protected string $url;

    /**
     * The claims that will be present in the JWT. If `null` only the member's id will be passed along.
     *
     * @var JWTClaims[]
     */
    #[Column(
        type: 'simple_array',
        nullable: true,
        enumType: JWTClaims::class,
    )]
    protected array $claims;

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getCallback(): string
    {
        return $this->callback;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return JWTClaims[]
     */
    public function getClaims(): array
    {
        if (empty($this->claims)) {
            return [JWTClaims::Lidnr];
        }

        return $this->claims;
    }
}
