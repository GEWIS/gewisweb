<?php

declare(strict_types=1);

namespace App\Security\User;

/**
 * Process-wide flag mirroring the `MFA_ENFORCEMENT` env value, populated once per request by
 * {@see \App\EventListener\User\MfaEnforcementBootListener} before the security stack first asks for a user's roles.
 *
 * Exists because {@see \App\Entity\User\User::getRoles()} cannot constructor-inject services (Doctrine entities are
 * plain objects), but it still needs to know whether to strip `ROLE_ADMIN` / `ROLE_BOARD` for in-scope users who have
 * not yet enrolled in MFA. Defaults to `true` so any path that bypasses the boot listener (e.g. early bootstrap) keeps
 * the production-safe behaviour.
 */
final class MfaEnforcementSwitch
{
    private static bool $enabled = true;

    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }
}
