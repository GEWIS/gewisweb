<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageAdminController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PageAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PageAdminController
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
