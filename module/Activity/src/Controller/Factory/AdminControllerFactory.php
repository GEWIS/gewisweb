<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminController;
use Activity\Mapper\Signup as SignupMapper;
use Activity\Service\AclService;
use Activity\Service\Activity as ActivityService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Activity\Service\Signup as SignupService;
use Activity\Service\SignupListQuery as SignupListQueryService;
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
            $container->get(ActivityService::class),
            $container->get(ActivityQueryService::class),
            $container->get(SignupService::class),
            $container->get(SignupListQueryService::class),
            $container->get(SignupMapper::class),
        );
    }
}
