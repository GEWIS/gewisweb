<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\MemberController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberController {
        return new MemberController(
            $container->get('decision_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('decision_service_member'),
            $container->get('decision_service_memberinfo'),
            $container->get('decision_service_decision'),
            $container->get('config')['regulations'],
        );
    }
}
