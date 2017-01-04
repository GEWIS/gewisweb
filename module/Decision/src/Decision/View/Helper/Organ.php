<?php

namespace Decision\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Organ extends AbstractHelper implements ServiceLocatorAwareInterface
{
    
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;
    
    /**
     * Returns the organ object for an abbreviation.
     *
     * @param $abbr string abbreviation of the organ
     * @return \Decision\Model\Organ
     */
    public function __invoke($abbr)
    {
        $organService = $this->getServiceLocator()->getServiceLocator()->get('Decision\Service\Organ');
        $organ = $organService->findOrganByAbbr($abbr);
        return $organ;
    }
}
