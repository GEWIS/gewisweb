<?php

declare(strict_types=1);

namespace Decision\Service\Factory;

use Decision\Mapper\Member as MemberMapper;
use Decision\Service\AclService;
use Decision\Service\MemberInfo as MemberInfoService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Service\Photo as PhotoService;
use Psr\Container\ContainerInterface;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;

class MemberInfoFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MemberInfoService {
        return new MemberInfoService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(PhotoService::class),
            $container->get(MemberMapper::class),
            $container->get(ApiAppAuthenticationMapper::class),
            $container->get('config')['photo'],
        );
    }
}
