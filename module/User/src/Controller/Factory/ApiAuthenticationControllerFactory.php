<?php

namespace User\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use User\Controller\ApiAuthenticationController;
use User\Service\ApiApp as ApiAppService;

class ApiAuthenticationControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return ApiAuthenticationController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ApiAuthenticationController {
        return new ApiAuthenticationController(
            $container->get('user_service_acl'),
            $container->get(ApiAppService::class),
        );
    }
}
