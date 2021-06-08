<?php


namespace Application\View\Helper;


use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;

class HighlightedVacancies extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Returns all visible categories
     *
     */
    public function __invoke()
    {
        $pluginManager = $this->getServiceLocator();
        $companyService = $pluginManager->getServiceLocator()->get('Company\Service\Company');
        return $companyService->getHighlightsList();
    }
}
