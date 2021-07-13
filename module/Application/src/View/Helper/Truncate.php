<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Truncate extends AbstractHelper
{
    /**
     * @param string $text string to truncate
     * @param int $length length at which to truncate
     * @param array $options options
     *
     * @return string truncated string
     *
     * @src http://www.codestance.com/tutorials-archive/zend-framework-truncate-view-helper-246
     */
    public function __invoke($text, $length = 100, $options = [])
    {
        $default = [
            'ending' => '...', 'exact' => false,
        ];
        $options = array_merge($default, $options);
        $ending = $options['ending'];
        $exact = $options['exact'];

        if (mb_strlen($text) <= $length) {
            // do not truncate
            return $text;
        }

        $truncate = mb_substr($text, 0, $length - mb_strlen($ending));

        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }
        $truncate .= $ending;

        return $truncate;
    }
}
