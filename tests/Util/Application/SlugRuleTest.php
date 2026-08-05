<?php

declare(strict_types=1);

namespace App\Tests\Util\Application;

use App\Util\Application\SlugRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SlugRuleTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function slugs(): iterable
    {
        yield 'a plain word' => [
            'nexunt',
            true,
        ];

        yield 'hyphens and digits after the first letter' => [
            'delta-robotics-2',
            true,
        ];

        yield 'underscores' => [
            'orbit_analytics',
            true,
        ];

        yield 'a single letter' => [
            'a',
            true,
        ];

        yield 'empty' => [
            '',
            false,
        ];

        yield 'starting with a digit' => [
            '3m',
            false,
        ];

        yield 'starting with a hyphen' => [
            '-nexunt',
            false,
        ];

        yield 'upper case' => [
            'Nexunt',
            false,
        ];

        yield 'a space' => [
            'delta robotics',
            false,
        ];

        yield 'a slash' => [
            'delta/robotics',
            false,
        ];

        yield 'a dot' => [
            'delta.robotics',
            false,
        ];

        yield 'a newline at the end' => [
            "nexunt\n",
            false,
        ];
    }

    #[DataProvider('slugs')]
    public function testWhatCountsAsASlug(
        string $slug,
        bool $acceptable,
    ): void {
        self::assertSame(
            $acceptable,
            SlugRule::matches($slug),
        );
    }
}
