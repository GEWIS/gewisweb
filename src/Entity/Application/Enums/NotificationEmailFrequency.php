<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use DateInterval;
use DateTimeImmutable;
use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How often a member's email notifications are batched into a digest. Even {@see Immediately} is grouped: the digest
 * job runs every few minutes, so it collects everything since the previous run rather than mailing per notification.
 */
enum NotificationEmailFrequency: string implements TranslatableInterface
{
    case Immediately = 'immediately';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';

    /**
     * Whether a digest is due now, given when the member last received one. A member who has never had one is always
     * due.
     */
    public function isDue(
        ?DateTimeImmutable $lastSentAt,
        DateTimeImmutable $now,
    ): bool {
        if (null === $lastSentAt) {
            return true;
        }

        return $lastSentAt->add($this->interval()) <= $now;
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Immediately => $translator->trans(
                'As they happen',
                locale: $locale,
            ),
            self::Hourly => $translator->trans(
                'Hourly',
                locale: $locale,
            ),
            self::Daily => $translator->trans(
                'Daily',
                locale: $locale,
            ),
            self::Weekly => $translator->trans(
                'Weekly',
                locale: $locale,
            ),
        };
    }

    private function interval(): DateInterval
    {
        return match ($this) {
            self::Immediately => new DateInterval('PT0S'),
            self::Hourly => new DateInterval('PT1H'),
            self::Daily => new DateInterval('P1D'),
            self::Weekly => new DateInterval('P7D'),
        };
    }
}
