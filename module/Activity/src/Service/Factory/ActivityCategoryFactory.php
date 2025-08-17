<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Form\ActivityCategory as ActivityCategoryForm;
use Activity\Mapper\ActivityCategory as ActivityCategoryMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ActivityCategoryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCategoryService {
        return new ActivityCategoryService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ActivityCategoryMapper::class),
            $container->get(ActivityCategoryForm::class),
        );
    }
}
