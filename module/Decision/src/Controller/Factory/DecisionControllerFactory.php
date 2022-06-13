<?php

namespace Decision\Controller\Factory;

use Decision\Controller\DecisionController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DecisionControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return DecisionController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): DecisionController {
        return new DecisionController(
            $container->get('decision_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('decision_service_decision'),
            $container->get('decision_fileReader'),
        );
    }
}
