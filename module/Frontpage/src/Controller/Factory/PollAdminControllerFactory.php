<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PollAdminController;
use Frontpage\Service\AclService;
use Frontpage\Service\Poll as PollService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PollAdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollAdminController {
        return new PollAdminController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PollService::class),
        );
    }
}
