<?php

declare(strict_types=1);

namespace App\Tests\Util\Application;

use App\Util\Application\SlugRule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function preg_match;
use function str_repeat;

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

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function boundedSlugs(): iterable
    {
        yield 'the shortest one that is worth typing' => [
            'wie',
            true,
        ];

        yield 'the longest one worth passing on' => [
            'a' . str_repeat(
                'b',
                31,
            ),
            true,
        ];

        yield 'one letter short' => [
            'wi',
            false,
        ];

        yield 'one character too long' => [
            'a' . str_repeat(
                'b',
                32,
            ),
            false,
        ];

        yield 'still not upper case' => [
            'Vereniging',
            false,
        ];
    }

    /**
     * Where the slug is the whole address, it is held between three and thirty-two characters as well.
     */
    #[DataProvider('boundedSlugs')]
    public function testWhatCountsAsABoundedSlug(
        string $slug,
        bool $acceptable,
    ): void {
        self::assertSame(
            $acceptable,
            1 === preg_match(
                SlugRule::BOUNDED_PATTERN,
                $slug,
            ),
        );
    }
}
