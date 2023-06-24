<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Controller\ApiAuthenticationController;
use User\Mapper\ApiApp as ApiAppMapper;
use User\Service\ApiApp as ApiAppService;

class ApiAuthenticationControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiAuthenticationController {
        return new ApiAuthenticationController(
            $container->get('user_service_acl'),
            $container->get(ApiAppService::class),
            $container->get('user_mapper_apiappauthentication'),
            $container->get(ApiAppMapper::class),
            $container->get('user_form_apiappauthorisation_initial'),
            $container->get('user_form_apiappauthorisation_reminder'),
        );
    }
}
