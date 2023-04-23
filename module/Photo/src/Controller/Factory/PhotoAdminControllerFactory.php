<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoAdminController;

class PhotoAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PhotoAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PhotoAdminController {
        return new PhotoAdminController(
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
        );
    }
}
