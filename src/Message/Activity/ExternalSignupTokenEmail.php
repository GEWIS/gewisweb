<?php

declare(strict_types=1);

namespace App\Message\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;

/**
 * Asynchronously email an external subscriber the link for a freshly-issued sign-up token. The token row is created
 * synchronously when the sign-up is made (so the sign-up is immediately treated as unverified); only the mail is
 * queued. The plaintext token travels on the bus because only its hash is stored and the link cannot otherwise be
 * rebuilt.
 */
class ExternalSignupTokenEmail
{
    public function __construct(
        private readonly int $externalSignupId,
        private readonly string $token,
        private readonly ExternalSignupVerificationPurpose $purpose,
    ) {
    }

    public function getExternalSignupId(): int
    {
        return $this->externalSignupId;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getPurpose(): ExternalSignupVerificationPurpose
    {
        return $this->purpose;
    }
}
