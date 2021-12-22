<?php

namespace Decision\Controller\Factory;

use Decision\Controller\DecisionController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DecisionControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return DecisionController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): DecisionController {
        return new DecisionController(
            $container->get('decision_service_acl'),
            $container->get('translator'),
            $container->get('decision_service_decision'),
            $container->get('decision_fileReader'),
        );
    }
}
