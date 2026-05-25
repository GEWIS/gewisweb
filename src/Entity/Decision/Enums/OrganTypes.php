<?php

declare(strict_types=1);

namespace App\Entity\Decision\Enums;

use Symfony\Component\Translation\TranslatableMessage;

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

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Committee => new TranslatableMessage('Committee'),
            self::AVC => new TranslatableMessage('GMM Committee'),
            self::Fraternity => new TranslatableMessage('Fraternity'),
            self::KCC => new TranslatableMessage('Financial Audit Committee'),
            self::AVW => new TranslatableMessage('GMM Taskforce'),
            self::RvA => new TranslatableMessage('Advisory Board'),
            self::SC => new TranslatableMessage('Voting Committee'),
        };
    }

    public function pluralLabel(): TranslatableMessage
    {
        return match ($this) {
            self::Committee => new TranslatableMessage('Committees'),
            self::AVC => new TranslatableMessage('GMM Committees'),
            self::Fraternity => new TranslatableMessage('Fraternities'),
            self::KCC => new TranslatableMessage('Financial Audit Committees'),
            self::AVW => new TranslatableMessage('GMM Taskforces'),
            self::RvA => new TranslatableMessage('Advisory Boards'),
            self::SC => new TranslatableMessage('Voting Committees'),
        };
    }
}
