<?php

declare(strict_types=1);

namespace Decision\Form\Factory;

use Decision\Form\OrganInformation as OrganInformationForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class OrganInformationFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): OrganInformationForm {
        $form = new OrganInformationForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('decision_hydrator'));

        return $form;
    }
}
