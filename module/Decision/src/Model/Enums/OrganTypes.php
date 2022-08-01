<?php

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
    case KKK = 'kkk';
    case AVW = 'avw';
    case RvA = 'rva';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Committee => $translator->translate('Committee'),
            self::AVC => $translator->translate('AV-committee'),
            self::Fraternity => $translator->translate('Fraternity'),
            self::KKK => $translator->translate('Audit Committee'),
            self::AVW => $translator->translate('GM Task Force'),
            self::RvA => $translator->translate('Advisory Board'),
        };
    }
}
