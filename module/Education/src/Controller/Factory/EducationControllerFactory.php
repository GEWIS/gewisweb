<?php

namespace Education\Controller\Factory;

use Education\Controller\EducationController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class EducationControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return EducationController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): EducationController {
        return new EducationController(
            $container->get('education_service_exam'),
            $container->get('education_form_searchcourse'),
        );
    }
}
