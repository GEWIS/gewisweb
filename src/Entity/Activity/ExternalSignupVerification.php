<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * A signed, single-purpose token tied to one {@see ExternalSignup}, modelled on {@see \App\Entity\User\PasswordReset}.
 *
 * Only the hash of the token is stored; the link carries `selector.verifier` and the verifier is checked with
 * `hash_equals` against {@see self::$hashedToken}. The {@see ExternalSignupVerificationPurpose} distinguishes the
 * short-lived double-opt-in token (a live one means the sign-up is still unverified) from the long-lived self-service
 * manage token. Rows are removed by the owning {@see ExternalSignup} via `onDelete: CASCADE`.
 */
#[Entity(repositoryClass: ExternalSignupVerificationRepository::class)]
#[Index(
    columns: ['selector'],
    name: 'IDX_external_signup_verification_selector',
)]
class ExternalSignupVerification
{
    use IdentifiableTrait;

    public const string HASH_ALGO = 'sha256';

    /**
     * The external sign-up this token belongs to.
     */
    #[ManyToOne(targetEntity: ExternalSignup::class)]
    #[JoinColumn(
        name: 'external_signup_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private ExternalSignup $externalSignup;

    #[Column(
        type: Types::STRING,
        enumType: ExternalSignupVerificationPurpose::class,
    )]
    private ExternalSignupVerificationPurpose $purpose;

    #[Column(type: Types::STRING)]
    private string $selector;

    #[Column(type: Types::STRING)]
    private string $hashedToken;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    public function __construct(
        ExternalSignup $externalSignup,
        ExternalSignupVerificationPurpose $purpose,
        string $selector,
        string $hashedToken,
        DateTimeImmutable $expiresAt,
    ) {
        $this->externalSignup = $externalSignup;
        $this->purpose = $purpose;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;
        $this->expiresAt = $expiresAt;
    }

    public function getExternalSignup(): ExternalSignup
    {
        return $this->externalSignup;
    }

    public function getPurpose(): ExternalSignupVerificationPurpose
    {
        return $this->purpose;
    }

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
