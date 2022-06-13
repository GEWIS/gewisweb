<?php

namespace Photo\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoController;

class PhotoControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PhotoController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PhotoController {
        return new PhotoController(
            $container->get('translator'),
            $container->get('photo_service_acl'),
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
            $container->get('config')['photo'],
        );
    }
}
