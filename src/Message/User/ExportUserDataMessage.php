<?php

declare(strict_types=1);

namespace App\Message\User;

/**
 * Requests an asynchronous export of the personal data GEWIS holds about a member.
 */
class ExportUserDataMessage
{
    public function __construct(
        private readonly int $lidnr,
    ) {
    }

    public function getLidnr(): int
    {
        return $this->lidnr;
    }
}
