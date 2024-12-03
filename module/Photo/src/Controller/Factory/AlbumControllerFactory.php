<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\AlbumController;
use Photo\Service\AclService;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;
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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(AlbumService::class),
            $container->get(PhotoService::class),
            $container->get('config')['photo'],
        );
    }
}
