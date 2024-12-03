<?php

declare(strict_types=1);

namespace Company\Form\Factory;

use Company\Form\JobCategory as JobCategoryForm;
use Company\Mapper\Category as CategoryMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class JobCategoryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): JobCategoryForm {
        return new JobCategoryForm(
            $container->get(MvcTranslator::class),
            $container->get(CategoryMapper::class),
        );
    }
}
