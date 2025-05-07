<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function array_merge;
use function mb_strlen;
use function mb_strrpos;
use function mb_substr;

class Truncate extends AbstractHelper
{
    /**
     * @param string $text    string to truncate
     * @param int    $length  length at which to truncate
     * @param array  $options options
     *
     * @return string truncated string
     *
     * @src http://www.codestance.com/tutorials-archive/zend-framework-truncate-view-helper-246
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __invoke(
        string $text,
        int $length = 100,
        array $options = [],
    ): string {
        $default = [
            'ending' => '...',
            'exact' => false,
        ];

        $options = array_merge($default, $options);
        $ending = $options['ending'];
        $exact = $options['exact'];

        if (mb_strlen($text) <= $length) {
            // do not truncate
            return $text;
        }

        $truncate = mb_substr($text, 0, $length - mb_strlen((string) $ending));

        if (false === $exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            $truncate = mb_substr($truncate, 0, $spacepos);
        }

        return $truncate . $ending;
    }
}
