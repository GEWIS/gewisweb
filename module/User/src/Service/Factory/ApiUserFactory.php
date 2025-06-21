<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Form\ApiToken as ApiTokenForm;
use User\Mapper\ApiUser as ApiUserMapper;
use User\Service\AclService;
use User\Service\ApiUser as ApiUserService;

class ApiUserFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiUserService {
        return new ApiUserService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ApiUserMapper::class),
            $container->get(ApiTokenForm::class),
        );
    }
}
