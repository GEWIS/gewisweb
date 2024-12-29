<?php

declare(strict_types=1);

namespace Photo\Controller\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Controller\AlbumAdminController;
use Photo\Service\AclService;
use Photo\Service\Admin as AdminService;
use Photo\Service\Album as AlbumService;
use Psr\Container\ContainerInterface;

class AlbumAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumAdminController {
        return new AlbumAdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(AdminService::class),
            $container->get(AlbumService::class),
            $container->get('config')['photo'],
        );
    }
}
