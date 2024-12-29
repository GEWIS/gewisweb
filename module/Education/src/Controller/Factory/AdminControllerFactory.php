<?php

declare(strict_types=1);

namespace Education\Controller\Factory;

use Education\Controller\AdminController;
use Education\Service\AclService;
use Education\Service\Course as CourseService;
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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(CourseService::class),
            $container->get('config')['education_temp'],
        );
    }
}
