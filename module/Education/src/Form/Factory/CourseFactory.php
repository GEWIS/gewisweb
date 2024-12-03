<?php

declare(strict_types=1);

namespace Education\Form\Factory;

use Education\Form\Course as CourseForm;
use Education\Mapper\Course as CourseMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class CourseFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CourseForm {
        $courseForm = new CourseForm(
            $container->get(MvcTranslator::class),
            $container->get(CourseMapper::class),
        );
        $courseForm->setHydrator($container->get('education_hydrator'));

        return $courseForm;
    }
}
