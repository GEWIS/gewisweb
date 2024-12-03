<?php

declare(strict_types=1);

namespace Frontpage\Form\Factory;

use Frontpage\Form\PollApproval as PollApprovalForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PollApprovalFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollApprovalForm {
        $form = new PollApprovalForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('frontpage_hydrator'));

        return $form;
    }
}
