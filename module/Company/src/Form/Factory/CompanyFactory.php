<?php

declare(strict_types=1);

namespace Company\Form\Factory;

use Company\Form\Company as CompanyForm;
use Company\Mapper\Company as CompanyMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class CompanyFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CompanyForm {
        return new CompanyForm(
            $container->get(MvcTranslator::class),
            $container->get(CompanyMapper::class),
        );
    }
}
