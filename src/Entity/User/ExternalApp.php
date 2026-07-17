<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\User\Enums\JWTClaims;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * ExternalApp model.
 */
#[Entity]
class ExternalApp
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
    private array $claims = [];

    /**
     * Whether the application may currently be used to authenticate.
     */
    #[Column(
        type: Types::BOOLEAN,
        options: ['default' => true],
    )]
    private bool $enabled = true;

    /**
     * The moment after which the application may no longer be used to authenticate, if any.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $expiresAt = null;

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

    public function setCallback(string $callback): void
    {
        $this->callback = $callback;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
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

    /**
     * @param JWTClaims[] $claims
     */
    public function setClaims(array $claims): void
    {
        $this->claims = $claims;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getExpiresAt(): ?DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTime $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * Whether the application may currently mint tokens: enabled and not past any expiration date.
     */
    public function isActive(): bool
    {
        return $this->enabled
            && (null === $this->expiresAt || $this->expiresAt > new DateTime());
    }
}
