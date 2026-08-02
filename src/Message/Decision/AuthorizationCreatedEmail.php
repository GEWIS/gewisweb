<?php

declare(strict_types=1);

namespace App\Message\Decision;

/**
 * Mails the authorizer and the recipient that a GMM authorization was granted.
 */
final readonly class AuthorizationCreatedEmail
{
    public function __construct(public int $authorizationId)
    {
    }
}
