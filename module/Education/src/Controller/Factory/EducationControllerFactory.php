<?php

declare(strict_types=1);

namespace Education\Controller\Factory;

use Education\Controller\EducationController;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class EducationControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): EducationController {
        return new EducationController(
            $container->get('education_service_course'),
            $container->get('education_form_searchcourse'),
        );
    }
}
