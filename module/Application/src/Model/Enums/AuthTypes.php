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
    case Member = 'member';
    case CompanyUser = 'company';
    case Api = 'api';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::None => $translator->translate('guest'),
            self::Member => $translator->translate('GEWIS member'),
            self::CompanyUser => $translator->translate('company representative'),
            self::Api => $translator->translate('API user'),
        };
    }
}
