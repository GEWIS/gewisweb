<?php

namespace Application\View\Helper;

use Application\Model\LocalisedText as LocalisedTextModel;
use Laminas\View\Helper\AbstractHelper;

class LocaliseText extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @param LocalisedTextModel $localisedText
     *
     * @return string The localised value of `$localisedText`
     */
    public function __invoke(LocalisedTextModel $localisedText): string
    {
        return $localisedText->getText();
    }
}
