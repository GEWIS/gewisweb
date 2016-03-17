<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Truncate extends AbstractHelper
{

    /**
     * @param string $text string to truncate
     * @param int    $length length at which to truncate
     * @param array  $options options
     * @return string truncated string
     */
    public function __invoke($text, $length = 100, $options = [])
    {
        $default = [
            'ending' => '...', 'exact' => false
        ];
        $options = array_merge($default, $options);
        $ending = $options['ending'];
        $exact = $options['exact'];

        if (mb_strlen($text) <= $length) {
            return $text;
        } else {
            $truncate = mb_substr($text, 0, $length - mb_strlen($ending));
        }

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
