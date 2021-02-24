<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class FeaturedCompanyPackage extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Returns currently active featurePackage
     *
     * @return \Company\Model\CompanyFeaturedPackage
     */
    public function __invoke()
    {
        $companyService = $this->getServiceLocator()->getServiceLocator()->get('Company\Service\Company');
        $featuredPackage = $companyService->getFeaturedPackage();
        return $featuredPackage;
    }
}
