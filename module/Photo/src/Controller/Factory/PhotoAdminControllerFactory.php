<?php

namespace Photo\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoAdminController;

class PhotoAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return PhotoAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): PhotoAdminController {
        return new PhotoAdminController(
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
            $container->get('doctrine.entitymanager.orm_default'),
        );
    }
}
