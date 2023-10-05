<?php

declare(strict_types=1);

namespace Education\Controller\Factory;

use Education\Controller\AdminController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get('education_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('education_service_course'),
            $container->get('config')['education_temp'],
        );
    }
}
