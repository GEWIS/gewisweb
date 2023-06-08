<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PageController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PageControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PageController {
        return new PageController(
            $container->get('frontpage_service_page'),
        );
    }
}
