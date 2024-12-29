<?php

declare(strict_types=1);

namespace Decision\Controller\Factory;

use Decision\Controller\MemberController;
use Decision\Service\AclService;
use Decision\Service\Decision as DecisionService;
use Decision\Service\Member as MemberService;
use Decision\Service\MemberInfo as MemberInfoService;
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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(MemberService::class),
            $container->get(MemberInfoService::class),
            $container->get(DecisionService::class),
            $container->get('config')['regulations'],
        );
    }
}
