<?php

namespace Application\Controller\Factory;

use Application\Controller\IndexController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return IndexController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): IndexController {
        return new IndexController();
    }
}
