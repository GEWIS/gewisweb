<?php

declare(strict_types=1);

namespace User\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Controller\ApiAuthenticationController;
use User\Form\ApiAppAuthorisation;
use User\Mapper\ApiApp as ApiAppMapper;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Service\AclService;
use User\Service\ApiApp as ApiAppService;

class ApiAuthenticationControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiAuthenticationController {
        return new ApiAuthenticationController(
            $container->get(AclService::class),
            $container->get(ApiAppService::class),
            $container->get(ApiAppAuthenticationMapper::class),
            $container->get(ApiAppMapper::class),
            $container->get(ApiAppAuthorisation::class),
            $container->get('user_form_apiappauthorisation_reminder'),
        );
    }
}
