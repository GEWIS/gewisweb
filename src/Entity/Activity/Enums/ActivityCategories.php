<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_filter;

/**
 * Enum for the (single, mandatory) category of an activity.
 */
enum ActivityCategories: string implements TranslatableInterface
{
    case ActiveSocial = 'active-social';
    case Announcement = 'announcement';
    case Career = 'career';
    case Competition = 'competition';
    case Conference = 'conference';
    case Cultural = 'cultural';
    case Education = 'education';
    case External = 'external';
    case InterestList = 'interest-list';
    case InterestMeeting = 'interest-meeting';
    case Meeting = 'meeting';
    case Party = 'party';
    case PromoMerch = 'promo-merch';
    case Recreational = 'recreational';
    case SaveTheDate = 'save-the-date';
    case SocialDrink = 'social-drink';
    case Sports = 'sports';
    case Weekend = 'weekend';
    case Workshop = 'workshop';
    case Other = 'other';

    // Only ever assigned to legacy activities by a migration; it must never be selectable for new activities.
    case Uncategorised = 'uncategorised';

    /**
     * All cases except the migration-only {@see self::Uncategorised}, for category selection and filtering.
     *
     * @return self[]
     */
    public static function selectableCases(): array
    {
        return array_filter(
            self::cases(),
            static fn (self $case): bool => self::Uncategorised !== $case,
        );
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::ActiveSocial => $translator->trans(
                'Active & social',
                locale: $locale,
            ),
            self::Announcement => $translator->trans(
                'Announcement',
                locale: $locale,
            ),
            self::Career => $translator->trans(
                'Career',
                locale: $locale,
            ),
            self::Competition => $translator->trans(
                'Competition',
                locale: $locale,
            ),
            self::Conference => $translator->trans(
                'Conference',
                locale: $locale,
            ),
            self::Cultural => $translator->trans(
                'Cultural',
                locale: $locale,
            ),
            self::Education => $translator->trans(
                'Education',
                locale: $locale,
            ),
            self::External => $translator->trans(
                'External',
                locale: $locale,
            ),
            self::InterestList => $translator->trans(
                'Interest list',
                locale: $locale,
            ),
            self::InterestMeeting => $translator->trans(
                'Interest meeting',
                locale: $locale,
            ),
            self::Meeting => $translator->trans(
                'Meeting',
                locale: $locale,
            ),
            self::Party => $translator->trans(
                'Party',
                locale: $locale,
            ),
            self::PromoMerch => $translator->trans(
                'Promo & merchandise',
                locale: $locale,
            ),
            self::Recreational => $translator->trans(
                'Recreational',
                locale: $locale,
            ),
            self::SaveTheDate => $translator->trans(
                'Save the date',
                locale: $locale,
            ),
            self::SocialDrink => $translator->trans(
                'Social drink',
                locale: $locale,
            ),
            self::Sports => $translator->trans(
                'Sports',
                locale: $locale,
            ),
            self::Weekend => $translator->trans(
                'Weekend',
                locale: $locale,
            ),
            self::Workshop => $translator->trans(
                'Workshop',
                locale: $locale,
            ),
            self::Other => $translator->trans(
                'Other',
                locale: $locale,
            ),
            self::Uncategorised => $translator->trans(
                'Uncategorised',
                locale: $locale,
            ),
        };
    }
}
