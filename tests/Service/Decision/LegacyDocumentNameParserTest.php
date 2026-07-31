<?php

declare(strict_types=1);

namespace App\Tests\Service\Decision;

use App\Service\Decision\LegacyDocumentNameParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LegacyDocumentNameParserTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: ?string, 2: string, 3: ?string, 4: ?string, 5: ?string}>
     */
    public static function nameProvider(): array
    {
        // name, point number, base name, version label, version date, reference key
        return [
            'AV Stuk prefix' => [
                'AV Stuk 105.3.1 - Notulen AV104',
                '3',
                'Notulen AV104',
                null,
                null,
                null,
            ],
            'AV Stuk prefix without sub-point' => [
                'AV stuk 104.5 - Beleidsplan',
                '5',
                'Beleidsplan',
                null,
                null,
                null,
            ],
            'point with version and date suffix' => [
                '2.1 Agenda (v1.2) (03-06-2020)',
                '2',
                'Agenda',
                'v1.2',
                '2020-06-03',
                null,
            ],
            'point with sub-point' => [
                '4.1 Conceptnotulen AV144',
                '4',
                'Conceptnotulen AV144',
                null,
                null,
                null,
            ],
            'point with dot separator' => [
                '3. Agenda',
                '3',
                'Agenda',
                null,
                null,
                null,
            ],
            'version suffix only' => [
                'Notulen AV 143 (v1.1)',
                null,
                'Notulen AV 143',
                'v1.1',
                null,
                null,
            ],
            'iso date suffix' => [
                'Begroting (2020-06-03)',
                null,
                'Begroting',
                null,
                '2020-06-03',
                null,
            ],
            'bare name' => [
                'Financieel jaarverslag',
                null,
                'Financieel jaarverslag',
                null,
                null,
                null,
            ],
            'year is not a point number' => [
                '2019 Begroting GEWIS',
                null,
                '2019 Begroting GEWIS',
                null,
                null,
                null,
            ],
            'scenarios reference' => [
                "Scenario's & procedures AV",
                null,
                "Scenario's & procedures AV",
                null,
                null,
                'scenarios-and-procedures',
            ],
            'reference with point prefix' => [
                'AV stuk 105.2 - Eternal Decisionlist',
                '2',
                'Eternal Decisionlist',
                null,
                null,
                'eternal-decision-list',
            ],
            'combined memorandum beats its halves' => [
                'Eternal Memorandum and Decision List',
                null,
                'Eternal Memorandum and Decision List',
                null,
                null,
                'eternal-memorandum-and-decision-list',
            ],
            'dutch summaries reference' => [
                "Samenvattingen oude AV's",
                null,
                "Samenvattingen oude AV's",
                null,
                null,
                'summaries-of-old-gmms',
            ],
            'leading whitespace trimmed' => [
                '  Huishoudelijk Reglement',
                null,
                'Huishoudelijk Reglement',
                null,
                null,
                null,
            ],
        ];
    }

    #[DataProvider('nameProvider')]
    public function testParse(
        string $rawName,
        ?string $pointNumber,
        string $baseName,
        ?string $versionLabel,
        ?string $versionDate,
        ?string $referenceKey,
    ): void {
        $parsed = new LegacyDocumentNameParser()->parse($rawName);

        self::assertSame(
            $pointNumber,
            $parsed->pointNumber,
        );
        self::assertSame(
            $baseName,
            $parsed->baseName,
        );
        self::assertSame(
            $versionLabel,
            $parsed->versionLabel,
        );
        self::assertSame(
            $versionDate,
            $parsed->versionDate?->format('Y-m-d'),
        );
        self::assertSame(
            $referenceKey,
            $parsed->referenceKey,
        );
    }

    public function testGroupKeyNormalisesCaseAndWhitespace(): void
    {
        $parser = new LegacyDocumentNameParser();

        self::assertSame(
            $parser->parse('2.1  Agenda (v1.0)')->groupKey,
            $parser->parse('2.2 AGENDA (v1.1)')->groupKey,
        );
    }
}
