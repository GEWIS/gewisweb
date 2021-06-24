<?php

namespace Application\View\Helper;

use Zend\Form\ElementInterface;
use Zend\View\Helper\AbstractHelper;

class LocalisedTextElement extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @param ElementInterface $element
     * @return string The real value of the $element
     */
    public function __invoke(ElementInterface $element)
    {
        $currentValue = $element->getValue();

        if (!is_null($currentValue)) {
            if (is_string($currentValue)) {
                return $currentValue;
            }

            if ($this->endsWith($currentValue->getAttribute('id'), 'en')) {
                return $currentValue->getValueEN();
            } elseif ($this->endsWith($currentValue->getAttribute('id'), 'nl')) {
                return $currentValue->getValueNL();
            }
        }

        return '';
    }

    /**
     * Checks whether a haystack ends with a needle. PHP does not offer this functionality natively until PHP 8.0.
     *
     * @param $haystack
     * @param $needle
     * @return boolean
     */
    private function endsWith(string $haystack, string $needle)
    {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}
