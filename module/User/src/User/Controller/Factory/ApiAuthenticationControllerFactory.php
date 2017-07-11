<?php

namespace User\Controller\Factory;

use User\Controller\ApiAuthenticationController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ApiAuthenticationControllerFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sl)
    {
        $sm = $sl->getServiceLocator();

        return new ApiAuthenticationController($sm->get('user_service_user'));
    }
}