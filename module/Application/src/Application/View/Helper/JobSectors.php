<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class JobSectors extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Returns all visible sectors
     *
     */
    public function __invoke($lang)
    {
        $pluginManager = $this->getServiceLocator();
        $companyService = $pluginManager->getServiceLocator()->get('Company\Service\Company');
        $sectors = $companyService->getSectorList($lang);
        return $sectors;
    }
}
