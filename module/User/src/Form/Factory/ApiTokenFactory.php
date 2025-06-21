<?php

declare(strict_types=1);

namespace User\Form\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;
use User\Form\ApiToken as ApiTokenForm;

class ApiTokenFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ApiTokenForm {
        $form = new ApiTokenForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('user_hydrator'));

        return $form;
    }
}
