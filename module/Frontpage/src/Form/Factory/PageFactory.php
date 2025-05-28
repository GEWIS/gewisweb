<?php

declare(strict_types=1);

namespace Frontpage\Form\Factory;

use Frontpage\Form\Page as PageForm;
use Frontpage\Mapper\Page as PageMapper;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PageFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PageForm {
        $form = new PageForm(
            $container->get(MvcTranslator::class),
            $container->get(PageMapper::class),
        );
        $form->setHydrator($container->get('frontpage_hydrator'));

        return $form;
    }
}
