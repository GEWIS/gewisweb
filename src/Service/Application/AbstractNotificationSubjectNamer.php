<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\LocalisedText;

/**
 * What every namer needs whichever domain it speaks for: a name read in both languages, and one that is the same in
 * both because the subject only has the one.
 */
abstract class AbstractNotificationSubjectNamer implements NotificationSubjectNamerInterface
{
    /**
     * @return array{en: string, nl: string}
     */
    protected function localised(LocalisedText $text): array
    {
        return [
            'en' => $text->getText(Languages::English) ?? '',
            'nl' => $text->getText(Languages::Dutch) ?? '',
        ];
    }

    /**
     * @return array{en: string, nl: string}
     */
    protected function plain(string $name): array
    {
        return [
            'en' => $name,
            'nl' => $name,
        ];
    }
}
