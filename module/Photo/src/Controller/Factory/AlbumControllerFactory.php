<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\AlbumController;
use Psr\Container\ContainerInterface;

class AlbumControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumController {
        return new AlbumController(
            $container->get('photo_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('photo_service_album'),
            $container->get('photo_service_photo'),
            $container->get('config')['photo'],
        );
    }
}
