<?php

declare(strict_types=1);

namespace Education\Form\Fieldset\Factory;

use Education\Form\Fieldset\Exam as ExamFieldset;
use Education\Hydrator\Strategy\ExamTypeHydratorStrategy;
use Education\Model\Exam as ExamModel;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ExamFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ExamFieldset {
        $fieldset = new ExamFieldset(
            $container->get(MvcTranslator::class),
        );
        $fieldset->setConfig($container->get('config'));
        $fieldset->setObject(new ExamModel());
        $hydrator = $container->get('education_hydrator_document');
        $hydrator->addStrategy('examType', new ExamTypeHydratorStrategy());
        $fieldset->setHydrator($hydrator);

        return $fieldset;
    }
}
