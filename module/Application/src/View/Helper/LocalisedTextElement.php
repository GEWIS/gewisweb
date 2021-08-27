<?php

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\View\Helper\AbstractHelper;

class LocalisedTextElement extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @param ElementInterface $element
     *
     * @return string The real value of the `$element`
     */
    public function __invoke(ElementInterface $element): string
    {
        $currentValue = $element->getValue();

        if (!is_null($currentValue)) {
            if (is_string($currentValue)) {
                return $currentValue;
            }

            if (str_ends_with($currentValue->getAttribute('id'), 'en')) {
                return $currentValue->getValueEN();
            } elseif (str_ends_with($currentValue->getAttribute('id'), 'nl')) {
                return $currentValue->getValueNL();
            }
        }

        return '';
    }
}
