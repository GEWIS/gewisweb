<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\NewsAdminController;
use Frontpage\Service\AclService;
use Frontpage\Service\News as NewsService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class NewsAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): NewsAdminController {
        return new NewsAdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(NewsService::class),
        );
    }
}
