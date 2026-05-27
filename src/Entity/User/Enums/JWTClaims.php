<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use App\Entity\Decision\Member as MemberModel;
use Symfony\Component\Translation\TranslatableMessage;

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
    case MiddleName = 'middle_name';

    public function getValue(MemberModel $member): bool|int|string|null
    {
        return match ($this) {
            self::Email => $member->getEmail(),
            self::FamilyName => $member->getLastName(),
            self::GivenName => $member->getFirstName(),
            self::Is18Plus => $member->hasReached18(),
            self::Lidnr => $member->getLidnr(),
            self::MembershipType => $member->getType()->value,
            self::MiddleName => $member->getMiddleName(),
        };
    }

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Email => new TranslatableMessage('Email address'),
            self::FamilyName => new TranslatableMessage('Family name'),
            self::GivenName => new TranslatableMessage('Given name'),
            self::Is18Plus => new TranslatableMessage('Is 18+?'),
            self::Lidnr => new TranslatableMessage('Member number'),
            self::MembershipType => new TranslatableMessage('Membership type'),
            self::MiddleName => new TranslatableMessage('Middle name'),
        };
    }
}
