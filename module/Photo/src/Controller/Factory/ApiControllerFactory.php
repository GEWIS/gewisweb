<?php

namespace Photo\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\ApiController;

class ApiControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ApiController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiController {
        return new ApiController(
            $container->get('photo_service_acl'),
            $container->get('translator'),
            $container->get('photo_mapper_tag'),
            $container->get('photo_mapper_vote'),
        );
    }
}
