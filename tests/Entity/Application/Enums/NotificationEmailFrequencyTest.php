<?php

declare(strict_types=1);

namespace App\Tests\Entity\Application\Enums;

use App\Entity\Application\Enums\NotificationEmailFrequency;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class NotificationEmailFrequencyTest extends TestCase
{
    public function testAMemberWhoHasNeverHadADigestIsAlwaysDue(): void
    {
        foreach (NotificationEmailFrequency::cases() as $frequency) {
            self::assertTrue($frequency->isDue(
                null,
                new DateTimeImmutable('2026-01-01 12:00'),
            ));
        }
    }

    public function testImmediatelyIsDueOnEveryRun(): void
    {
        $moment = new DateTimeImmutable('2026-01-01 12:00');

        self::assertTrue(NotificationEmailFrequency::Immediately->isDue(
            $moment,
            $moment,
        ));
    }

    public function testHourlyIsDueOnlyOnceTheHourHasPassed(): void
    {
        $lastSentAt = new DateTimeImmutable('2026-01-01 12:00');

        self::assertFalse(NotificationEmailFrequency::Hourly->isDue(
            $lastSentAt,
            new DateTimeImmutable('2026-01-01 12:30'),
        ));
        self::assertTrue(NotificationEmailFrequency::Hourly->isDue(
            $lastSentAt,
            new DateTimeImmutable('2026-01-01 13:00'),
        ));
    }

    public function testWeeklyIsDueOnlyOnceTheWeekHasPassed(): void
    {
        $lastSentAt = new DateTimeImmutable('2026-01-01 12:00');

        self::assertFalse(NotificationEmailFrequency::Weekly->isDue(
            $lastSentAt,
            new DateTimeImmutable('2026-01-05 12:00'),
        ));
        self::assertTrue(NotificationEmailFrequency::Weekly->isDue(
            $lastSentAt,
            new DateTimeImmutable('2026-01-08 12:00'),
        ));
    }
}
