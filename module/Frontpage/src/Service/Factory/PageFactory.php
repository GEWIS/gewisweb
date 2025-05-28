<?php

declare(strict_types=1);

namespace Frontpage\Service\Factory;

use Application\Service\FileStorage as FileStorageService;
use Frontpage\Form\Page as PageForm;
use Frontpage\Mapper\Page as PageMapper;
use Frontpage\Service\AclService;
use Frontpage\Service\Page as PageService;
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
    ): PageService {
        return new PageService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(FileStorageService::class),
            $container->get(PageMapper::class),
            $container->get(PageForm::class),
            $container->get('config')['storage'],
        );
    }
}
