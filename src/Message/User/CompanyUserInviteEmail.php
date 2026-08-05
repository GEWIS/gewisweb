<?php

declare(strict_types=1);

namespace App\Message\User;

/**
 * Asynchronously email somebody the link that lets them start representing a company. The invitation row is written
 * synchronously so the admin interface can show it right away; only the mail is queued. The plaintext token travels on
 * the bus because only its hash is stored and the link cannot otherwise be rebuilt.
 */
class CompanyUserInviteEmail
{
    public function __construct(
        private readonly int $inviteId,
        private readonly string $token,
    ) {
    }

    public function getInviteId(): int
    {
        return $this->inviteId;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
