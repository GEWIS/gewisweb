<?php

declare(strict_types=1);

namespace Activity\Form\Factory;

use Activity\Form\Activity as ActivityForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActivityFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityForm {
        $form = new ActivityForm($container->get(MvcTranslator::class));
        $form->setHydrator($container->get('activity_hydrator'));

        return $form;
    }
}
