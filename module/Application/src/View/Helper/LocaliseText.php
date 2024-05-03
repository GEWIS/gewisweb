<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Model\LocalisedText as LocalisedTextModel;
use Laminas\View\Helper\AbstractHelper;

class LocaliseText extends AbstractHelper
{
    /**
     * Returns the localised value of an element.
     */
    public function __invoke(LocalisedTextModel $localisedText): string
    {
        return $localisedText->getText() ?? '';
    }
}
