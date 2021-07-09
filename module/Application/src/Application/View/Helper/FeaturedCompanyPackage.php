<?php

namespace Application\View\Helper;

use Company\Model\CompanyFeaturedPackage;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class FeaturedCompanyPackage extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Returns currently active featurePackage
     *
     * @return CompanyFeaturedPackage
     */
    public function __invoke()
    {
        $companyService = $this->getServiceLocator()->getServiceLocator()->get('Company\Service\Company');
        $featuredPackage = $companyService->getFeaturedPackage();
        return $featuredPackage;
    }
}
