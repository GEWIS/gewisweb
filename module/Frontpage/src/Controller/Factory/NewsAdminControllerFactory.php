<?php

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\NewsAdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class NewsAdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return NewsAdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): NewsAdminController {
        return new NewsAdminController(
            $container->get('frontpage_service_news'),
            $container->get('frontpage_service_acl'),
            $container->get('translator'),
        );
    }
}
