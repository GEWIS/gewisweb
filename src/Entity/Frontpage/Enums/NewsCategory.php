<?php

declare(strict_types=1);

namespace App\Entity\Frontpage\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What a news item is about, which is what the filter on the news feed narrows by. The backing value doubles as the
 * value in the query string.
 */
enum NewsCategory: string implements TranslatableInterface
{
    case Board = 'board';
    case Association = 'association';
    case Career = 'career';
    case Education = 'education';
    case Committees = 'committees';

    /**
     * The `.badge-*` modifier the category is drawn with, so each one is recognisable at a glance wherever news is
     * listed.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Board => 'badge-gewis-primary',
            self::Association => 'badge-success',
            self::Career => 'badge-info',
            self::Education => 'badge-warning',
            self::Committees => 'badge-secondary',
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Board => $translator->trans(
                'Board',
                locale: $locale,
            ),
            self::Association => $translator->trans(
                'Association',
                locale: $locale,
            ),
            self::Career => $translator->trans(
                'Career',
                locale: $locale,
            ),
            self::Education => $translator->trans(
                'Education',
                locale: $locale,
            ),
            self::Committees => $translator->trans(
                'Committees',
                locale: $locale,
            ),
        };
    }
}
