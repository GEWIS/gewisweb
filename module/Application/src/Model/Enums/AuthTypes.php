<?php

declare(strict_types=1);

namespace Application\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * The different ways someone can authenticate with the website.
 */
enum AuthTypes: string
{
    case None = 'none';
    case User = 'user';
    case CompanyUser = 'company_user';
    case Api = 'api';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::None => $translator->translate('none'),
            self::User => $translator->translate('user'),
            self::CompanyUser => $translator->translate('company user'),
            self::Api => $translator->translate('API user'),
        };
    }
}
