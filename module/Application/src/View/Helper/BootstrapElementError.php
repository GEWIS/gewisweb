<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\View\Helper\AbstractHelper;

use function count;

class BootstrapElementError extends AbstractHelper
{
    /**
     * Checks if the input has a Bootstrap error.
     *
     * @return string A Bootstrap class
     */
    public function __invoke(ElementInterface $element): string
    {
        return count($element->getMessages()) > 0 ? 'has-error' : '';
    }
}
