<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function mb_stripos;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function transliterator_transliterate;

class HighlightSearch extends AbstractHelper
{
    /**
     * Insert `<mark>` around a search prompt in the content of a returned result.
     */
    public function __invoke(
        string $query,
        string $content,
    ): string {
        // Convert content to something that is easily searchable (i.e. it MUST contain only Latin-ASCII characters).
        $transliteratedContent = transliterator_transliterate('Any-Latin; Latin-ASCII', $content);
        // Do the same for the search prompt, as otherwise searches WITH non-ASCII characters will not work.
        $query = transliterator_transliterate('Any-Latin; Latin-ASCII', $query);

        $offset = 0;
        $output = '';
        $length = mb_strlen($query);

        // There is a very important assumption here; the transliterated version of the content MUST be exactly as long
        // as the original version. Otherwise, the marker insertion is done with an incorrect offset. As such, using
        // `iconv` is NOT an option as it will either extend (e.g. `€` becomes `EUR`) or completely remove characters
        // (i.e. the `//IGNORE` option).
        while (false !== ($position = mb_stripos($transliteratedContent, $query, $offset, 'UTF-8'))) {
            // Progressively insert markers into the original content.
            $output .= sprintf(
                '%s%s%s%s',
                mb_substr($content, $offset, $position - $offset, 'UTF-8'),
                '<mark>',
                mb_substr($content, $position, $length, 'UTF-8'),
                '</mark>',
            );

            $offset = $position + $length;
        }

        // Add the final part of the content back.
        $output .= mb_substr($content, $offset, null, 'UTF-8');

        return $output;
    }
}
