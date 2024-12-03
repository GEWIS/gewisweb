<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminCategoryController;
use Activity\Service\AclService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminCategoryControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminCategoryController {
        return new AdminCategoryController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ActivityCategoryService::class),
        );
    }
}
