<?php

declare(strict_types=1);

namespace Photo\Controller\Factory\Plugin;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\Plugin\AlbumPlugin;
use Psr\Container\ContainerInterface;

class AlbumPluginFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumPlugin {
        return new AlbumPlugin(
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
            $container->get('config')['photo'],
        );
    }
}
