<?php

namespace Photo\Controller\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoController;

class PhotoControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return PhotoController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): PhotoController {
        return new PhotoController(
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
        );
    }
}
