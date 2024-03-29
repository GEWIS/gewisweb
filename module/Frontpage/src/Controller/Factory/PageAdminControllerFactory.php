<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageAdminController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PageAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PageAdminController {
        return new PageAdminController(
            $container->get('frontpage_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('frontpage_service_page'),
        );
    }
}
