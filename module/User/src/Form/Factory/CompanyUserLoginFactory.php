<?php

declare(strict_types=1);

namespace User\Form\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Form\CompanyUserLogin as CompanyUserLoginForm;

class CompanyUserLoginFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyUserLoginForm {
        return new CompanyUserLoginForm(
            $container->get(MvcTranslator::class),
            $container->get('config')['passwords']['min_length_companyUser'],
        );
    }
}
