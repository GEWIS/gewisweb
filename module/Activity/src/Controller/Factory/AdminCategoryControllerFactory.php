<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminCategoryController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminCategoryControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return AdminCategoryController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminCategoryController {
        return new AdminCategoryController(
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_category'),
        );
    }
}
