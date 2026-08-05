<?php

declare(strict_types=1);

namespace App\Entity\Career\Enums;

use App\Entity\Application\Enums\ImageProfile;
use App\Entity\Application\Enums\ImageVariant;
use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The shape a banner is bought in. Both are ordinary display-advertising sizes, so a company hands over the artwork it
 * already has rather than having something made for us.
 *
 * The format fixes the box the banner is shown in, which is why an image is only accepted at exactly those
 * proportions: a banner is artwork with a logo and a line of text on it, and anything that crops or squashes it ruins
 * the thing that was paid for.
 */
enum CompanyBannerFormats: string implements TranslatableInterface
{
    /** The wide, shallow strip that sits between two news items without pushing them apart. */
    case Leaderboard = 'leaderboard';

    /** The taller block, for a company that wants to be harder to scroll past. */
    case Billboard = 'billboard';

    /**
     * @return positive-int
     */
    public function width(): int
    {
        return match ($this) {
            self::Leaderboard => 728,
            self::Billboard => 970,
        };
    }

    /**
     * @return positive-int
     */
    public function height(): int
    {
        return match ($this) {
            self::Leaderboard => 90,
            self::Billboard => 250,
        };
    }

    /**
     * The rendition shown on an ordinary display; {@see retinaVariant()} is the one behind it on a denser screen.
     */
    public function variant(): ImageVariant
    {
        return match ($this) {
            self::Leaderboard => ImageVariant::Leaderboard,
            self::Billboard => ImageVariant::Billboard,
        };
    }

    public function retinaVariant(): ImageVariant
    {
        return match ($this) {
            self::Leaderboard => ImageVariant::Leaderboard2x,
            self::Billboard => ImageVariant::Billboard2x,
        };
    }

    public function imageProfile(): ImageProfile
    {
        return match ($this) {
            self::Leaderboard => ImageProfile::CompanyBannerLeaderboard,
            self::Billboard => ImageProfile::CompanyBannerBillboard,
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Leaderboard => $translator->trans(
                'Leaderboard (%width% by %height% pixels)',
                [
                    '%width%' => $this->width(),
                    '%height%' => $this->height(),
                ],
                locale: $locale,
            ),
            self::Billboard => $translator->trans(
                'Billboard (%width% by %height% pixels)',
                [
                    '%width%' => $this->width(),
                    '%height%' => $this->height(),
                ],
                locale: $locale,
            ),
        };
    }
}
