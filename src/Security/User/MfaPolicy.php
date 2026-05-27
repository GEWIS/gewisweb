<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Entity\User\Enums\UserRoles;
use App\Entity\User\User;

/**
 * Canonical "is MFA required for this user" check, consulted by the disable controller, the access-denied listener,
 * and the security index template.
 *
 * In scope:
 *  - Users with an active `ROLE_ADMIN` {@see \App\Entity\User\UserRole}.
 *  - Users currently installed on the board (per `Member::isBoardMember()`).
 *
 * The entity-side filtering in {@see User::getRoles()} duplicates the rule (it cannot inject services) but consults
 * the same {@see MfaEnforcementSwitch} as this service, so the two paths agree.
 */
final class MfaPolicy
{
    public function __construct(private readonly bool $mfaEnforcementEnabled)
    {
    }

    public function isRequiredFor(User $user): bool
    {
        if (!$this->mfaEnforcementEnabled) {
            return false;
        }

        if ($user->getMember()->isBoardMember()) {
            return true;
        }

        foreach ($user->getRoleEntities() as $userRole) {
            if (
                UserRoles::Admin === $userRole->getRole()
                && $userRole->isActive()
            ) {
                return true;
            }
        }

        return false;
    }

    public function hasEnrolled(User $user): bool
    {
        return null !== $user->getTotpSecret();
    }
}
