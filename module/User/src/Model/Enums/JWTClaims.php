<?php

namespace User\Model\Enums;

use Decision\Model\Member as MemberModel;

/**
 * Enum for keeping track of the claims that can be present in the JWT for ApiApps.
 */
enum JWTClaims: string
{
    case Email = 'email';
    case FamilyName = 'family_name';
    case GivenName = 'given_name';
    case Is18Plus = 'is_18_plus';
    case Lidnr = 'lidnr';
    case MembershipType = 'membership_type';

    public function getValue(MemberModel $member): bool|int|string
    {
        return match($this) {
            self::Email => $member->getEmail(),
            self::FamilyName => $member->getLastName(),
            self::GivenName => $member->getFirstName(),
            self::Is18Plus => $member->is18Plus(),
            self::Lidnr => $member->getLidnr(),
            self::MembershipType => $member->getType(),
        };
    }
}
