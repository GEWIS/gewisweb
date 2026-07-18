<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\User\Enums\DeviceTypes;
use App\Repository\User\SessionRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;

/**
 * @psalm-type SessionGdprArrayType = array{
 *     series: string,
 *     firewall: string,
 *     deviceType: string,
 *     browser: ?string,
 *     operatingSystem: ?string,
 *     ipAddress: string,
 *     userAgent: string,
 *     createdAt: string,
 *     lastUsedAt: string,
 *     expiresAt: string,
 *     expired: bool,
 * }
 */
#[Entity(repositoryClass: SessionRepository::class)]
#[Index(fields: ['userIdentifier', 'firewallName'])]
#[Index(fields: ['series'])]
#[Index(fields: ['expiresAt'])]
#[Index(fields: ['phpSessionId'])]
class Session
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: Types::INTEGER)]
    private int $id;

    /**
     * The public series identifier, persisted across token rotations.
     * Stored in the browser cookie alongside the raw token.
     */
    #[Column(
        type: Types::STRING,
        unique: true,
    )]
    private string $series;

    /**
     * SHA-256(rawToken). Raw token is never stored.
     */
    #[Column(type: Types::STRING)]
    private string $hashedToken;

    /**
     * HMAC of the immutable row fields. Detects DB tampering.
     */
    #[Column(type: Types::STRING)]
    private string $signature;

    /**
     * SHA-256 hash of the user's signature_properties values at session creation.
     */
    #[Column(type: Types::STRING)]
    private string $signaturePropertiesHash;

    /**
     * Which Symfony firewall this session belongs to.
     */
    #[Column(type: Types::STRING)]
    private string $firewallName;

    /**
     * The user this session belongs to (e.g. email or UUID).
     */
    #[Column(type: Types::STRING)]
    private string $userIdentifier;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $lastUsedAt;

    #[Column(type: Types::TEXT)]
    private string $userAgent;

    #[Column(type: Types::STRING)]
    private string $ipAddress;

    /**
     * Semantic device class; resolved to an icon glyph at render time via {@see DeviceTypes::icon()}.
     */
    #[Column(enumType: DeviceTypes::class)]
    private DeviceTypes $deviceType;

    /**
     * Parsed from userAgent (e.g. "Chrome 124"); for bots holds the bot name on its own. Nullable when the User Agent
     * is empty or unrecognised.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $browser = null;

    /**
     * Parsed from userAgent (e.g. "Android 14"). Nullable for bots / empty UAs / OS-less environments.
     */
    #[Column(
        type: Types::STRING,
        nullable: true,
    )]
    private ?string $operatingSystem = null;

    /**
     * The PHP session ID this row is bound to. Allows for direct destruction of the device's session via the Redis
     * session handler.
     */
    #[Column(type: Types::STRING)]
    private string $phpSessionId;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSeries(): string
    {
        return $this->series;
    }

    public function setSeries(string $series): void
    {
        $this->series = $series;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function setHashedToken(string $hashedToken): void
    {
        $this->hashedToken = $hashedToken;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    public function getSignaturePropertiesHash(): string
    {
        return $this->signaturePropertiesHash;
    }

    public function setSignaturePropertiesHash(string $hash): void
    {
        $this->signaturePropertiesHash = $hash;
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    public function setFirewallName(string $firewallName): void
    {
        $this->firewallName = $firewallName;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    public function getLastUsedAt(): DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(DateTimeImmutable $lastUsedAt): void
    {
        $this->lastUsedAt = $lastUsedAt;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getDeviceType(): DeviceTypes
    {
        return $this->deviceType;
    }

    public function setDeviceType(DeviceTypes $deviceType): void
    {
        $this->deviceType = $deviceType;
    }

    public function getBrowser(): ?string
    {
        return $this->browser;
    }

    public function setBrowser(?string $browser): void
    {
        $this->browser = $browser;
    }

    public function getOperatingSystem(): ?string
    {
        return $this->operatingSystem;
    }

    public function setOperatingSystem(?string $operatingSystem): void
    {
        $this->operatingSystem = $operatingSystem;
    }

    public function getPhpSessionId(): string
    {
        return $this->phpSessionId;
    }

    public function setPhpSessionId(string $phpSessionId): void
    {
        $this->phpSessionId = $phpSessionId;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable();
    }

    /**
     * The non-secret device and timing details of the session. The token, its hashes, and the PHP session id are
     * deliberately left out.
     *
     * @return SessionGdprArrayType
     */
    public function toGdprArray(): array
    {
        return [
            'series' => $this->getSeries(),
            'firewall' => $this->getFirewallName(),
            'deviceType' => $this->getDeviceType()->value,
            'browser' => $this->getBrowser(),
            'operatingSystem' => $this->getOperatingSystem(),
            'ipAddress' => $this->getIpAddress(),
            'userAgent' => $this->getUserAgent(),
            'createdAt' => $this->getCreatedAt()->format(DateTimeInterface::ATOM),
            'lastUsedAt' => $this->getLastUsedAt()->format(DateTimeInterface::ATOM),
            'expiresAt' => $this->getExpiresAt()->format(DateTimeInterface::ATOM),
            'expired' => $this->isExpired(),
        ];
    }
}
