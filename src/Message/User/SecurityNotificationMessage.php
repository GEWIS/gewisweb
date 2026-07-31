<?php

declare(strict_types=1);

namespace App\Message\User;

use App\Entity\Application\Enums\NotificationType;
use DateTimeImmutable;

/**
 * Something happened to an account that whoever owns it should be told about: it was signed in, its password changed,
 * its second factor was turned on or off.
 *
 * Everything the warning needs travels with the message. Keep it that way: narrowing this down to the id of a row that
 * was written moments ago would let the worker run the lookup before the request has committed, and the warning would
 * quietly go missing.
 */
final readonly class SecurityNotificationMessage
{
    /**
     * @param array{browser?: string, system?: string, address?: string} $origin
     */
    public function __construct(
        private string $firewallName,
        private string $userIdentifier,
        private NotificationType $type,
        private array $origin,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public function getFirewallName(): string
    {
        return $this->firewallName;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    /**
     * @return array{browser?: string, system?: string, address?: string}
     */
    public function getOrigin(): array
    {
        return $this->origin;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
