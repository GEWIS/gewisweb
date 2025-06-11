<?php

declare(strict_types=1);

namespace Decision\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different organ types.
 */
enum OrganTypes: string
{
    case Committee = 'committee';
    case AVC = 'avc';
    case Fraternity = 'fraternity';
    case KCC = 'kcc';
    case AVW = 'avw';
    case RvA = 'rva';
    case SC = 'sc';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Committee => $translator->translate('Committee'),
            self::AVC => $translator->translate('GMM Committee'),
            self::Fraternity => $translator->translate('Fraternity'),
            self::KCC => $translator->translate('Financial Audit Committee'),
            self::AVW => $translator->translate('GMM Taskforce'),
            self::RvA => $translator->translate('Advisory Board'),
            self::SC => $translator->translate('Voting Committee'),
        };
    }

    public function getPluralName(Translator $translator): string
    {
        return match ($this) {
            self::Committee => $translator->translate('Committees'),
            self::AVC => $translator->translate('GMM Committees'),
            self::Fraternity => $translator->translate('Fraternities'),
            self::KCC => $translator->translate('Financial Audit Committees'),
            self::AVW => $translator->translate('GMM Taskforces'),
            self::RvA => $translator->translate('Advisory Boards'),
            self::SC => $translator->translate('Voting Committees'),
        };
    }
}
