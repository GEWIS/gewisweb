<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\Enums\JWTClaims;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

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
    #[Column(type: Types::STRING)]
    private string $appId;

    /**
     * Application secret.
     */
    #[Column(type: Types::STRING)]
    private string $secret;

    /**
     * Callback URL.
     */
    #[Column(type: Types::STRING)]
    private string $callback;

    /**
     * URL for the application when the user does not authorise access.
     */
    #[Column(type: Types::STRING)]
    private string $url;

    /**
     * The claims that will be present in the JWT. If `null` only the member's id will be passed along.
     *
     * @var JWTClaims[]
     */
    #[Column(
        type: Types::SIMPLE_ARRAY,
        nullable: true,
        enumType: JWTClaims::class,
    )]
    private array $claims;

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
        if ([] === $this->claims) {
            return [JWTClaims::Lidnr];
        }

        return $this->claims;
    }
}
