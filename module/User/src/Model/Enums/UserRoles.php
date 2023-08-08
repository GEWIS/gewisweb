<?php

declare(strict_types=1);

namespace User\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for all the possible roles that exist within the ACL.
 */
enum UserRoles: string
{
    case Guest = 'guest';
    case TueGuest = 'tueguest';
    case ApiUser = 'apiuser';
    case Company = 'company';
    case User = 'user';
    case Graduate = 'graduate';
    case ActiveMember = 'active_member';
    case CompanyAdmin = 'company_admin';
    case Admin = 'admin';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Guest => $translator->translate('Guest'),
            self::TueGuest => $translator->translate('TU/e Guest'),
            self::ApiUser => $translator->translate('API User'),
            self::Company => $translator->translate('Company'),
            self::User => $translator->translate('User'),
            self::Graduate => $translator->translate('Graduate'),
            self::ActiveMember => $translator->translate('Active Member'),
            self::CompanyAdmin => $translator->translate('Company Admin'),
            self::Admin => $translator->translate('Admin'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getFormValueOptions(Translator $translator): array
    {
        return [
            self::Guest->value => $translator->translate('Guest - Anyone who can access the website'),
            self::TueGuest->value => $translator->translate(
                'TU/e Guest - Anyone who can access the website from within the TU/e',
            ),
            self::ApiUser->value => $translator->translate('API User - Authenticated automated program'),
            self::Company->value => $translator->translate('Company - Authenticated company representative'),
            self::User->value => $translator->translate('User - Authenticated member'),
            self::Graduate->value => $translator->translate('Graduate - Authenticated graduate'),
            self::ActiveMember->value => $translator->translate('Active Member - Authenticated member and in an organ'),
            self::CompanyAdmin->value => $translator->translate('Company Admin - C4 members'),
            self::Admin->value => $translator->translate('Admin - Board and Tom'),
        ];
    }
}
