<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extensions;

use App\Twig\Extensions\HighlightSearchExtension;
use PHPUnit\Framework\TestCase;

final class HighlightSearchExtensionTest extends TestCase
{
    /**
     * @param list<string> $terms
     */
    private function highlight(
        string $content,
        array $terms,
    ): string {
        return new HighlightSearchExtension()->highlight(
            $content,
            $terms,
        );
    }

    public function testMarksEveryCaseInsensitiveOccurrence(): void
    {
        self::assertSame(
            'De <mark>borrel</mark> na de <mark>Borrel</mark>',
            $this->highlight(
                'De borrel na de Borrel',
                ['borrel'],
            ),
        );
    }

    public function testMatchesAcrossAccents(): void
    {
        self::assertSame(
            "Het <mark>besl\u{00fa}it</mark> staat",
            $this->highlight(
                "Het besl\u{00fa}it staat",
                ['besluit'],
            ),
        );
    }

    public function testEscapesContentAndMatchesEscapedTerms(): void
    {
        self::assertSame(
            'Stemmen over &lt;b&gt; en <mark>R&amp;D</mark>',
            $this->highlight(
                'Stemmen over <b> en R&D',
                ['R&D'],
            ),
        );
    }

    public function testOverlappingTermsMergeIntoOneMark(): void
    {
        self::assertSame(
            'De <mark>kerstboomborrel</mark> komt',
            $this->highlight(
                'De kerstboomborrel komt',
                [
                    'kerstboom',
                    'boomborrel',
                ],
            ),
        );
    }

    public function testNoTermsReturnsTheEscapedContent(): void
    {
        self::assertSame(
            'a &amp; b',
            $this->highlight(
                'a & b',
                [],
            ),
        );
    }
}
