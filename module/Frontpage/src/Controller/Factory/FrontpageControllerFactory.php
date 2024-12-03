<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\FrontpageController;
use Frontpage\Service\Frontpage as FrontpageService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class FrontpageControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): FrontpageController {
        return new FrontpageController(
            $container->get(FrontpageService::class),
        );
    }
}
