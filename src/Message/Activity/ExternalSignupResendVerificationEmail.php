<?php

declare(strict_types=1);

namespace App\Message\Activity;

/**
 * Asynchronously re-send the double-opt-in confirmation email for an external sign-up, for when the original was lost.
 * Mirrors {@see \App\Message\User\PasswordResetRequestEmail}: the controller dispatches this unconditionally and the
 * whole sign-up existence check happens in the handler, off the request thread, so the HTTP response timing can never
 * reveal whether the email address is actually signed up.
 */
class ExternalSignupResendVerificationEmail
{
    public function __construct(
        private readonly int $signupListId,
        private readonly string $email,
    ) {
    }

    public function getSignupListId(): int
    {
        return $this->signupListId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
