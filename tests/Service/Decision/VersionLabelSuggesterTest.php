<?php

declare(strict_types=1);

namespace App\Tests\Service\Decision;

use App\Service\Decision\VersionLabelSuggester;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VersionLabelSuggesterTest extends TestCase
{
    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function labelProvider(): array
    {
        return [
            'no previous version' => [
                null,
                'v1.0',
            ],
            'blank previous label' => [
                '  ',
                'v1.0',
            ],
            'minor bump' => [
                'v1.0',
                'v1.1',
            ],
            'double digit minor' => [
                'v1.9',
                'v1.10',
            ],
            'kept prefix casing' => [
                'V2.3',
                'V2.4',
            ],
            'no prefix' => [
                '1.0',
                '1.1',
            ],
            'free-form label echoed' => [
                'final',
                'final',
            ],
            'suffixed label echoed' => [
                'v1.0-draft',
                'v1.0-draft',
            ],
        ];
    }

    #[DataProvider('labelProvider')]
    public function testSuggest(
        ?string $previousLabel,
        string $expected,
    ): void {
        self::assertSame(
            $expected,
            new VersionLabelSuggester()->suggest($previousLabel),
        );
    }
}
