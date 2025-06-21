<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\View\Helper\AbstractHelper;

use function is_string;
use function str_ends_with;

class LocalisedTextElement extends AbstractHelper
{
    /**
     * Determines the correct value for an element.
     *
     * @return string The real value of the `$element`
     */
    public function __invoke(ElementInterface $element): string
    {
        $currentValue = $element->getValue();

        if (null !== $currentValue) {
            if (is_string($currentValue)) {
                return $currentValue;
            }

            if (str_ends_with((string) $currentValue->getAttribute('id'), 'en')) {
                return $currentValue->getValueEN();
            }

            if (str_ends_with((string) $currentValue->getAttribute('id'), 'nl')) {
                return $currentValue->getValueNL();
            }
        }

        return '';
    }
}
