<?php

declare(strict_types=1);

namespace Activity\Form\Factory;

use Activity\Form\SignupList as SignupListForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class SignupListFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SignupListForm {
        $form = new SignupListForm($container->get(MvcTranslator::class));
        $form->setHydrator($container->get('activity_hydrator'));

        return $form;
    }
}
