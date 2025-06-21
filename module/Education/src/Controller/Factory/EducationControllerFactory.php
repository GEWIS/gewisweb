<?php

declare(strict_types=1);

namespace Education\Controller\Factory;

use Education\Controller\EducationController;
use Education\Form\SearchCourse as SearchCourseForm;
use Education\Service\Course as CourseService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class EducationControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): EducationController {
        return new EducationController(
            $container->get(CourseService::class),
            $container->get(SearchCourseForm::class),
        );
    }
}
