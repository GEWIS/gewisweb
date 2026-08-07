<?php

declare(strict_types=1);

namespace App\Entity\Education\Enums;

use Symfony\Component\Translation\TranslatableMessage;

enum CourseSort: string
{
    case MostMaterial = 'material';
    case Code = 'code';
    case RecentlyUpdated = 'updated';

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::MostMaterial => new TranslatableMessage('Most material'),
            self::Code => new TranslatableMessage('Course code'),
            self::RecentlyUpdated => new TranslatableMessage('Recently updated'),
        };
    }
}
