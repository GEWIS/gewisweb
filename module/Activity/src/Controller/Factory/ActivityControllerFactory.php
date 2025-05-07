<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityController;
use Activity\Mapper\Signup as SignupMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Activity\Service\Signup as SignupService;
use Activity\Service\SignupListQuery as SignupListQueryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ActivityControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityController {
        return new ActivityController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(SignupMapper::class),
            $container->get(ActivityQueryService::class),
            $container->get(SignupService::class),
            $container->get(SignupListQueryService::class),
        );
    }
}
