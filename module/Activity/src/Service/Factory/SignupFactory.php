<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Mapper\Signup as SignupMapper;
use Activity\Mapper\SignupFieldValue as SignupFieldValueMapper;
use Activity\Mapper\SignupOption as SignupOptionMapper;
use Activity\Service\AclService;
use Activity\Service\Signup as SignupService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SignupFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SignupService {
        return new SignupService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get('doctrine.entitymanager.orm_default'),
            $container->get(SignupMapper::class),
            $container->get(SignupFieldValueMapper::class),
            $container->get(SignupOptionMapper::class),
        );
    }
}
