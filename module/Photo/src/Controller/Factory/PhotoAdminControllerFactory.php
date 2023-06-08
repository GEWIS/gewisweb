<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoAdminController;
use Psr\Container\ContainerInterface;

class PhotoAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
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
