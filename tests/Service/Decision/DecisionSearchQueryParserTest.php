<?php

declare(strict_types=1);

namespace App\Tests\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Service\Decision\DecisionSearchQueryParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecisionSearchQueryParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<string>, 2: list<string>, 3: ?MeetingTypes, 4: string}>
     */
    public static function promptProvider(): array
    {
        // prompt, include terms, exclude terms, type, remainder
        return [
            'bare words' => [
                'kerstboom borrel',
                [
                    'kerstboom',
                    'borrel',
                ],
                [],
                null,
                'kerstboom borrel',
            ],
            'quoted phrase' => [
                '"financieel jaarverslag"',
                ['financieel jaarverslag'],
                [],
                null,
                '',
            ],
            'excluded word' => [
                'begroting -afrekening',
                ['begroting'],
                ['afrekening'],
                null,
                'begroting',
            ],
            'excluded phrase' => [
                '-"besluit tot decharge"',
                [],
                ['besluit tot decharge'],
                null,
                '',
            ],
            'member-facing type filter' => [
                'type:bm Example',
                ['Example'],
                [],
                MeetingTypes::BV,
                'Example',
            ],
            'internal type filter' => [
                'type:ALV borrel',
                ['borrel'],
                [],
                MeetingTypes::ALV,
                'borrel',
            ],
            'unknown type keyword stays text' => [
                'type:x86',
                ['type:x86'],
                [],
                null,
                'type:x86',
            ],
            'meeting reference stays in the remainder' => [
                'BM 1805.3.1',
                [
                    'BM',
                    '1805.3.1',
                ],
                [],
                null,
                'BM 1805.3.1',
            ],
            'lone dash is a term' => [
                '-',
                ['-'],
                [],
                null,
                '-',
            ],
        ];
    }

    /**
     * @param list<string> $includeTerms
     * @param list<string> $excludeTerms
     */
    #[DataProvider('promptProvider')]
    public function testParse(
        string $prompt,
        array $includeTerms,
        array $excludeTerms,
        ?MeetingTypes $type,
        string $remainder,
    ): void {
        $query = new DecisionSearchQueryParser()->parse($prompt);

        self::assertSame(
            $includeTerms,
            $query->includeTerms,
        );
        self::assertSame(
            $excludeTerms,
            $query->excludeTerms,
        );
        self::assertSame(
            $type,
            $query->type,
        );
        self::assertSame(
            $remainder,
            $query->remainder,
        );
    }

    public function testEmptyPromptIsEmpty(): void
    {
        self::assertTrue(new DecisionSearchQueryParser()->parse('')->isEmpty());
        self::assertFalse(new DecisionSearchQueryParser()->parse('type:bm')->isEmpty());
    }
}
