<?php

declare(strict_types=1);

namespace User\Model\Enums;

use Decision\Model\Member as MemberModel;
use Laminas\Mvc\I18n\Translator;

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

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Email => $translator->translate('E-mail address'),
            self::FamilyName => $translator->translate('Family name'),
            self::GivenName => $translator->translate('Given name'),
            self::Is18Plus => $translator->translate('Is 18+?'),
            self::Lidnr => $translator->translate('Member number'),
            self::MembershipType => $translator->translate('Membership type'),
            self::MiddleName => $translator->translate('Middle name'),
        };
    }
}
