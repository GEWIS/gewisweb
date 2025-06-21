<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Application\Service\Infimum as InfimumService;
use Frontpage\Controller\InfimumController;
use Frontpage\Service\AclService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class InfimumControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): InfimumController {
        return new InfimumController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(InfimumService::class),
        );
    }
}
