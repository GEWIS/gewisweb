<?php

declare(strict_types=1);

namespace App\Twig\Extensions;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use function htmlspecialchars;
use function max;
use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function transliterator_transliterate;
use function usort;

use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Wraps `<mark>` around search terms in a piece of text. Matching happens on a Latin-ASCII transliteration of both
 * sides, so a search for "besluit" also marks "beslúít". The transliteration of every character is exactly one
 * character long, which is what makes mapping match offsets back onto the original text safe; `iconv` would not be
 * (it expands or drops characters). The filter escapes the text itself, so it must receive the raw value.
 */
final class HighlightSearchExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'highlight_search',
                $this->highlight(...),
                ['is_safe' => ['html']],
            ),
        ];
    }

    /**
     * @param list<string> $terms
     */
    public function highlight(
        string $content,
        array $terms,
    ): string {
        $escaped = htmlspecialchars(
            $content,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        $ranges = [];
        $searchable = $this->searchable($escaped);
        foreach ($terms as $term) {
            $needle = $this->searchable(htmlspecialchars(
                $term,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8',
            ));
            if ('' === $needle) {
                continue;
            }

            $length = mb_strlen($needle);
            $offset = 0;
            while (
                false !== ($position = mb_stripos(
                    $searchable,
                    $needle,
                    $offset,
                    'UTF-8',
                ))
            ) {
                $ranges[] = [
                    $position,
                    $position + $length,
                ];
                $offset = $position + $length;
            }
        }

        if ([] === $ranges) {
            return $escaped;
        }

        usort(
            $ranges,
            static fn (array $a, array $b): int => $a[0] <=> $b[0],
        );

        // Merge overlapping ranges while emitting: an open range only closes once the next one starts past it.
        $output = '';
        $cursor = 0;
        $openStart = -1;
        $openEnd = -1;
        foreach ($ranges as [$start, $end]) {
            if (
                $openEnd >= 0
                && $start <= $openEnd
            ) {
                $openEnd = max(
                    $openEnd,
                    $end,
                );
                continue;
            }

            if ($openEnd >= 0) {
                $output .= $this->mark(
                    $escaped,
                    $cursor,
                    $openStart,
                    $openEnd,
                );
                $cursor = $openEnd;
            }

            $openStart = $start;
            $openEnd = $end;
        }

        $output .= $this->mark(
            $escaped,
            $cursor,
            $openStart,
            $openEnd,
        );

        return $output . mb_substr(
            $escaped,
            $openEnd,
        );
    }

    private function mark(
        string $escaped,
        int $cursor,
        int $start,
        int $end,
    ): string {
        return mb_substr(
            $escaped,
            $cursor,
            $start - $cursor,
        ) . '<mark>' . mb_substr(
            $escaped,
            $start,
            $end - $start,
        ) . '</mark>';
    }

    /**
     * The Latin-ASCII shadow of a string used for matching; the original when the transliteration would shift offsets.
     */
    private function searchable(string $value): string
    {
        $transliterated = transliterator_transliterate(
            'Any-Latin; Latin-ASCII',
            $value,
        );

        if (
            false === $transliterated
            || mb_strlen($transliterated) !== mb_strlen($value)
        ) {
            return $value;
        }

        return $transliterated;
    }
}
