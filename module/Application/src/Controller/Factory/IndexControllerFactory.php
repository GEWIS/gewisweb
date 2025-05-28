<?php

declare(strict_types=1);

namespace Application\Controller\Factory;

use Application\Controller\IndexController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class IndexControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): IndexController {
        return new IndexController();
    }
}
