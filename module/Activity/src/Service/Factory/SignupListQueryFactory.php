<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Mapper\SignupList as SignupListMapper;
use Activity\Service\AclService;
use Activity\Service\SignupListQuery as SignupListQueryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class SignupListQueryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SignupListQueryService {
        return new SignupListQueryService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(SignupListMapper::class),
        );
    }
}
