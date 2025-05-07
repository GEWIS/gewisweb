<?php

declare(strict_types=1);

namespace Company\Form\Factory;

use Company\Form\Job as JobForm;
use Company\Mapper\Category as CategoryMapper;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\Label as LabelMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class JobFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): JobForm {
        return new JobForm(
            $container->get(MvcTranslator::class),
            $container->get(JobMapper::class),
            $container->get(CategoryMapper::class)->findAll(),
            $container->get(LabelMapper::class)->findAll(),
        );
    }
}
