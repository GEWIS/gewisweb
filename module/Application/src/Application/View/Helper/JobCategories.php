<?php

namespace Application\View\Helper;

use Company\Model\CompanyFeaturedPackage;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class JobCategories extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Returns all visible categories
     *
     * @return CompanyFeaturedPackage
     */
    public function __invoke()
    {
        $pluginManager = $this->getServiceLocator();
        $companyService = $pluginManager->getServiceLocator()->get('Company\Service\Company');
        $categories = $companyService->getCategoryList(true);
        return $categories;
    }
}
