<?php

declare(strict_types=1);

namespace App\Entity\User\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How much of a member's tagged-photo collection is hidden from others on their photo page. {@see self::Selected} keys
 * off the photos the member picked (a hidden-photo list that survives untagging and re-tagging); the photo always stays
 * visible in its own album regardless.
 */
enum PhotoVisibility: string implements TranslatableInterface
{
    /** Every tagged photo is shown. */
    case HideNone = 'none';

    /** Only the photos the member picked are hidden. */
    case HideSelected = 'selected';

    /** Every tagged photo is hidden. */
    case HideAll = 'all';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::HideNone => $translator->trans(
                'Show all photos I am tagged in',
                locale: $locale,
            ),
            self::HideSelected => $translator->trans(
                'Hide only the photos I select',
                locale: $locale,
            ),
            self::HideAll => $translator->trans(
                'Hide all photos I am tagged in',
                locale: $locale,
            ),
        };
    }
}
