<?php

declare(strict_types=1);

namespace App\Security\User;

/**
 * The application's security firewalls, and the routes each one sends a user to. Symfony's FirewallMap exposes no
 * per-firewall route metadata, so the mapping lives here and is shared by the guards, listeners and handlers that need
 * it, keyed off the firewall name they already have.
 */
enum Firewall: string
{
    case Main = 'main';
    case Company = 'company';

    public function loginRoute(): string
    {
        return match ($this) {
            self::Main => 'user_login',
            self::Company => 'company_user_login',
        };
    }

    public function sudoConfirmRoute(): string
    {
        return match ($this) {
            self::Main => 'user_sudo_confirm',
            self::Company => 'company_user_sudo_confirm',
        };
    }

    /**
     * The multi-factor enrolment route, or null for a firewall that has none (only main members enrol here).
     */
    public function mfaEnableRoute(): ?string
    {
        return match ($this) {
            self::Main => 'user_mfa_enable',
            self::Company => null,
        };
    }
}
