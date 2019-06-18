<?php

namespace Application\View\Helper;

use Zend\Form\ElementInterface;
use Zend\View\Helper\AbstractHelper;

class BootstrapElementError extends AbstractHelper
{
    /**
     * Checks if the input has a Bootstrap error
     *
     * @param ElementInterface $element
     * @return string A Bootstrap class
     */
    public function __invoke(ElementInterface $element)
    {
        return count($element->getMessages()) > 0 ? 'has-error' : '';
    }
}