<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Which subscribers of a sign-up list a bulk email is addressed to. Mirrors the
 * Subscribee -> Admittee -> Attendee progression: {@see self::All} is every subscriber, {@see self::Admitted} and
 * {@see self::Waitlisted} the drawn / not-drawn halves of a limited-capacity list, and {@see self::Present} the
 * attendees. {@see self::Selected} cuts across all of these -- the rows the organiser ticked by hand.
 */
enum RecipientScope: string implements TranslatableInterface
{
    /** Everyone signed up for the list. */
    case All = 'all';

    /** Only the rows the organiser ticked. */
    case Selected = 'selected';

    /** The admittees of a limited-capacity list (drawn === true). */
    case Admitted = 'admitted';

    /** The waiting list of a limited-capacity list (drawn === false). */
    case Waitlisted = 'waitlisted';

    /** The attendees: those marked present. */
    case Present = 'present';

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::All => $translator->trans(
                'All subscribers',
                locale: $locale,
            ),
            self::Selected => $translator->trans(
                'Selected',
                locale: $locale,
            ),
            self::Admitted => $translator->trans(
                'Admittees',
                locale: $locale,
            ),
            self::Waitlisted => $translator->trans(
                'Waiting list',
                locale: $locale,
            ),
            self::Present => $translator->trans(
                'Attendees (present)',
                locale: $locale,
            ),
        };
    }
}
