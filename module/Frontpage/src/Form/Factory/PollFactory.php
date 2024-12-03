<?php

declare(strict_types=1);

namespace Frontpage\Form\Factory;

use Frontpage\Form\Poll as PollForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PollFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollForm {
        $form = new PollForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('frontpage_hydrator'));

        return $form;
    }
}
