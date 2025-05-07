<?php

declare(strict_types=1);

namespace Frontpage\Form\Factory;

use Frontpage\Form\PollComment as PollCommentForm;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PollCommentFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollCommentForm {
        $form = new PollCommentForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('frontpage_hydrator'));

        return $form;
    }
}
