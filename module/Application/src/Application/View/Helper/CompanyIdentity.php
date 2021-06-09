<?php


namespace Application\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;
use Zend\Authentication\AuthenticationServiceInterface;
use Zend\View\Exception;

class CompanyIdentity extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Retrieve the current identity, if any.
     *
     * If none available, returns null.
     *
     * @throws Exception\RuntimeException
     * @return mixed|null
     */
    public function __invoke()
    {
        $pluginManager = $this->getServiceLocator();
        $companyAuthenticationService = $pluginManager->getServiceLocator()->get('User\Service\Company');


        if (!$companyAuthenticationService->hasIdentity()) {
            return;
        }

        return $companyAuthenticationService->getIdentity();
    }
}
