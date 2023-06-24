<?php

declare(strict_types=1);

namespace Education\Model\Enums;

use Laminas\Mvc\I18n\Translator;

use function array_map;
use function array_merge;

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

    /**
     * @return array<array-key, ExamTypes|string>
     */
    public static function values(): array
    {
        return array_merge(
            array_map(
                static fn (self $status) => $status->value,
                self::cases(),
            ),
            self::cases(),
        );
    }
}
