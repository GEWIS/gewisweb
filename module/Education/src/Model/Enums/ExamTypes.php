<?php

declare(strict_types=1);

namespace Education\Model\Enums;

use Laminas\Mvc\I18n\Translator;

/**
 * Enum for the different exam types.
 */
enum ExamTypes: string
{
    case Final = 'exam';
    case Interim = 'interim';
    case Answers = 'answers';
    case Other = 'other';

    public function getName(Translator $translator): string
    {
        return match ($this) {
            self::Final => $translator->translate('Final'),
            self::Interim => $translator->translate('Interim'),
            self::Answers => $translator->translate('Answers'),
            self::Other => $translator->translate('Other'),
        };
    }

    public static function values(): array
    {
        return array_merge(
            array_map(
                fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
