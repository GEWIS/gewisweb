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

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Committee => $translator->translate('Committee'),
            self::AVC => $translator->translate('GMM Committee'),
            self::Fraternity => $translator->translate('Fraternity'),
            self::KCC => $translator->translate('Financial Audit Committee'),
            self::AVW => $translator->translate('GMM Taskforce'),
            self::RvA => $translator->translate('Advisory Board'),
        };
    }
}
