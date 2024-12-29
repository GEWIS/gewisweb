<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\PhotoAdminController;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;
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
            $container->get(AlbumService::class),
            $container->get(PhotoService::class),
        );
    }
}
