<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extensions;

use App\Twig\Extensions\DateRangeExtension;
use DateTimeImmutable;
use Locale;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * The range helper collapses a same-day span to one date, abbreviates a multi-day span, and only appends times/years
 * when asked. Assertions pin the English locale so weekday/month abbreviations are stable.
 */
final class DateRangeExtensionTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        Locale::setDefault('en');
    }

    public function testSingleDayWithoutTime(): void
    {
        self::assertSame(
            'Thursday 3 September',
            $this->extension()->dateRange(
                new DateTimeImmutable('2026-09-03 12:40:00'),
                new DateTimeImmutable('2026-09-03 13:20:00'),
                time: false,
                year: 'never',
            ),
        );
    }

    public function testMultiDayWithoutTimeIsAbbreviated(): void
    {
        self::assertSame(
            'Thu. 3 Sep. - Sat. 5 Sep.',
            $this->extension()->dateRange(
                new DateTimeImmutable('2026-09-03 12:40:00'),
                new DateTimeImmutable('2026-09-05 13:20:00'),
                time: false,
                year: 'never',
            ),
        );
    }

    public function testSingleDayWithTime(): void
    {
        self::assertSame(
            'Thursday 3 Sep. 12:40 - 13:20',
            $this->extension()->dateRange(
                new DateTimeImmutable('2026-09-03 12:40:00'),
                new DateTimeImmutable('2026-09-03 13:20:00'),
                time: true,
                year: 'never',
            ),
        );
    }

    public function testMultiDayWithTime(): void
    {
        self::assertSame(
            'Thu. 3 Sep. (12:40) - Sat. 5 Sep. (13:20)',
            $this->extension()->dateRange(
                new DateTimeImmutable('2026-09-03 12:40:00'),
                new DateTimeImmutable('2026-09-05 13:20:00'),
                time: true,
                year: 'never',
            ),
        );
    }

    public function testYearAlwaysShown(): void
    {
        self::assertSame(
            'Thu. 3 Sep. 2026 - Sat. 5 Sep. 2026',
            $this->extension()->dateRange(
                new DateTimeImmutable('2026-09-03'),
                new DateTimeImmutable('2026-09-05'),
                time: false,
                year: 'always',
            ),
        );
    }

    public function testActivityPresetKeepsTimesAndShowsDifferingYear(): void
    {
        // A year other than the current one is always shown by the 'auto' preset, so 1999 is deterministic.
        self::assertSame(
            'Friday 3 Sep. 1999 12:40 - 13:20',
            $this->extension()->activityDateRange(
                new DateTimeImmutable('1999-09-03 12:40:00'),
                new DateTimeImmutable('1999-09-03 13:20:00'),
            ),
        );
    }

    public function testActivityBadgeSplitsMultiDaySameMonth(): void
    {
        self::assertSame(
            [
                'weekday' => 'Thu - Sat',
                'day' => '03 - 05',
                'month' => 'Sep',
                'range' => true,
            ],
            $this->extension()->activityDateBadge(
                new DateTimeImmutable('2026-09-03'),
                new DateTimeImmutable('2026-09-05'),
            ),
        );
    }

    private function extension(): DateRangeExtension
    {
        return new DateRangeExtension();
    }
}
