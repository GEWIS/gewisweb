<?php

declare(strict_types=1);

namespace Education\Form\Fieldset\Factory;

use Education\Form\Fieldset\Summary as SummaryFieldset;
use Education\Model\Summary as SummaryModel;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SummaryFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SummaryFieldset {
        $fieldset = new SummaryFieldset(
            $container->get(MvcTranslator::class),
        );
        $fieldset->setConfig($container->get('config'));
        $fieldset->setObject(new SummaryModel());
        $fieldset->setHydrator($container->get('education_hydrator_document'));

        return $fieldset;
    }
}
