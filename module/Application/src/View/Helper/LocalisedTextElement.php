<?php

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\View\Helper\AbstractHelper;

class LocalisedTextElement extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @return string The real value of the $element
     */
    public function __invoke(ElementInterface $element)
    {
        $currentValue = $element->getValue();

        if (!is_null($currentValue)) {
            if (is_string($currentValue)) {
                return $currentValue;
            }

            if (LocalisedTextElement::endsWith($currentValue->getAttribute('id'), 'en')) {
                return $currentValue->getValueEN();
            } elseif (LocalisedTextElement::endsWith($currentValue->getAttribute('id'), 'nl')) {
                return $currentValue->getValueNL();
            }
        }

        return '';
    }

    /**
     * Checks whether a haystack ends with a needle. PHP does not offer this functionality natively until PHP 8.0.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private static function endsWith(string $haystack, string $needle)
    {
        return 0 === substr_compare($haystack, $needle, -strlen($needle));
    }
}
