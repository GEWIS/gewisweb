<?php

declare(strict_types=1);

namespace App\Entity\Application\Traits;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;

/**
 * Shared columns for a split-token credential: the link carries `selector.verifier`, only the hash of the verifier is
 * stored, and the row expires. Used by {@see \App\Entity\User\PasswordReset} and
 * {@see \App\Entity\Activity\ExternalSignupVerification}. The using class owns its own `#[Index]` on `selector` and its
 * own constructor (which assigns these properties — a using class may write trait `private` members).
 */
trait SelectorTokenTrait
{
    public const string HASH_ALGO = 'sha256';

    #[Column(type: Types::STRING)]
    private string $selector;

    #[Column(type: Types::STRING)]
    private string $hashedToken;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    public function getSelector(): string
    {
        return $this->selector;
    }

    public function getHashedToken(): string
    {
        return $this->hashedToken;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTimeImmutable('now');
    }
}
