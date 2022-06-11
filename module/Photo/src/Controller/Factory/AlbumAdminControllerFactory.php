<?php

namespace Photo\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\AlbumAdminController;

class AlbumAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AlbumAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumAdminController {
        return new AlbumAdminController(
            $container->get('photo_service_admin'),
            $container->get('photo_service_album'),
        );
    }
}
