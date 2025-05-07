<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\AdminController;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(DecisionService::class),
        );
    }
}
