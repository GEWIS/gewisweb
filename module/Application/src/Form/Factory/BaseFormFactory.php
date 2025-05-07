<?php

declare(strict_types=1);

namespace Application\Form\Factory;

use Laminas\Form\Form;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class BaseFormFactory implements FactoryInterface
{
    /**
     * @template T of Form
     *
     * @psalm-param class-string<T> $requestedName
     *
     * @return T
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ) {
        return new $requestedName($container->get(MvcTranslator::class));
    }
}
