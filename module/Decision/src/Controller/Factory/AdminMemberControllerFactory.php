<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\AdminMemberController;
use Decision\Service\AclService;
use Decision\Service\Gdpr as GdprService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminMemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminMemberController {
        return new AdminMemberController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(GdprService::class),
        );
    }
}
