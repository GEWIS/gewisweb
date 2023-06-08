<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Model\LocalisedText as LocalisedTextModel;
use Laminas\View\Helper\AbstractHelper;

class LocaliseText extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @return string|null The localised value of `$localisedText` or null if no translation exists.
     */
    public function __invoke(LocalisedTextModel $localisedText): ?string
    {
        return $localisedText->getText();
    }
}
