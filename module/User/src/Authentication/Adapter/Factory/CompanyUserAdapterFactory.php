<?php

declare(strict_types=1);

namespace User\Authentication\Adapter\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Authentication\Adapter\CompanyUserAdapter;
use User\Authentication\Service\LoginAttempt as LoginAttemptService;
use User\Mapper\CompanyUser as CompanyUserMapper;
use User\Service\PwnedPasswords as PwnedPasswordsService;

class CompanyUserAdapterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyUserAdapter {
        return new CompanyUserAdapter(
            $container->get(MvcTranslator::class),
            $container->get('user_bcrypt'),
            $container->get(LoginAttemptService::class),
            $container->get(PwnedPasswordsService::class),
            $container->get(CompanyUserMapper::class),
        );
    }
}
