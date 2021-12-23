<?php

namespace Photo\Controller\Factory\Plugin;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\Plugin\AlbumPlugin;

class AlbumPluginFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AlbumPlugin
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
