<?php

declare(strict_types=1);

namespace App\Security\User;

/**
 * Maps Symfony firewall names to their PersistentSignatureRememberMeHandler instances.
 *
 * Handlers are injected via session.yaml. The map is immutable at runtime.
 */
final class HandlerRegistry
{
    /** @param array<string, PersistentSignatureRememberMeHandler> $handlers */
    public function __construct(
        private readonly array $handlers,
    ) {
    }

    public function get(string $firewallName): ?PersistentSignatureRememberMeHandler
    {
        return $this->handlers[$firewallName] ?? null;
    }
}
