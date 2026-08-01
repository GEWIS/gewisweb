<?php

declare(strict_types=1);

namespace App\Message\Decision;

/**
 * Mails the recipient that a GMM authorization was revoked by its authorizer.
 */
final readonly class AuthorizationRevokedEmail
{
    public function __construct(public int $authorizationId)
    {
    }
}
