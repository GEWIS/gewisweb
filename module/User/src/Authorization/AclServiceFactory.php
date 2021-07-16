<?php

namespace User\Authorization;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use User\Service\AclService;

class AclServiceFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null): GenericAclService
    {
        $translator = $container->get('translator');
        $authService = $container->get('user_auth_service');
        $remoteAddress = $container->get('user_remoteaddress');
        $tueRange = $container->get('config')['tue_range'];
        switch ($requestedName) {
            case 'user_service_acl':
                return new AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'activity_service_acl':
                return new \Activity\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'company_service_acl':
                return new \Company\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'decision_service_acl':
                return new \Decision\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'education_service_acl':
                return new \Education\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'frontpage_service_acl':
                return new \Frontpage\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            case 'photo_service_acl':
                return new \Photo\Service\AclService($translator, $authService, $remoteAddress, $tueRange);
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        "The service with name %s could not be found and was therefore not created.",
                        $requestedName
                    )
                );
        }
    }
}
