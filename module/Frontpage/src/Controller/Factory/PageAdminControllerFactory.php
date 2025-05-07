<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageAdminController;
use Frontpage\Service\AclService;
use Frontpage\Service\Page as PageService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PageAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PageAdminController {
        return new PageAdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PageService::class),
        );
    }
}
