<?php

declare(strict_types=1);

namespace App\Message\User;

class RevokeSessionsRealtimeMessage
{
    /**
     * @param string[] $series
     */
    public function __construct(
        private readonly string $firewallName,
        private readonly array $series,
    ) {
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    /**
     * @return string[]
     */
    public function getSeries(): array
    {
        return $this->series;
    }
}
