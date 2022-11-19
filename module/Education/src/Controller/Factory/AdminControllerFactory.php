<?php

namespace Education\Controller\Factory;

use Education\Controller\AdminController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminController {
        return new AdminController(
            $container->get('education_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('education_service_exam'),
            $container->get('config')['education_temp'],
        );
    }
}
