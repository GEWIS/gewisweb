<?php

declare(strict_types=1);

namespace Decision\Service\Factory;

use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Service\AclService;
use Decision\Service\Member as MemberService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MemberFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberService {
        return new MemberService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(MemberMapper::class),
            $container->get(AuthorizationMapper::class),
        );
    }
}
