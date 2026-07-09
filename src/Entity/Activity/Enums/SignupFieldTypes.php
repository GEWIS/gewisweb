<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The kind of answer a {@see \App\Entity\Activity\SignupField} collects.
 */
enum SignupFieldTypes: string implements TranslatableInterface
{
    /** A free-text answer. */
    case Text = 'text';

    /** A yes/no answer. */
    case YesNo = 'yes-no';

    /** A numeric answer, optionally bounded by min/max. */
    case Number = 'number';

    /** One of a fixed set of options. */
    case Choice = 'choice';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Text => $translator->trans(
                'Text',
                locale: $locale,
            ),
            self::YesNo => $translator->trans(
                'Yes/No',
                locale: $locale,
            ),
            self::Number => $translator->trans(
                'Number',
                locale: $locale,
            ),
            self::Choice => $translator->trans(
                'Choice',
                locale: $locale,
            ),
        };
    }
}
