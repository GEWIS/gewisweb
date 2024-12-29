<?php

declare(strict_types=1);

namespace Photo\Form\Factory;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Photo\Form\Album as AlbumForm;
use Psr\Container\ContainerInterface;

class AlbumFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AlbumForm {
        $form = new AlbumForm(
            $container->get(MvcTranslator::class),
        );
        $form->setHydrator($container->get('photo_hydrator'));

        return $form;
    }
}
