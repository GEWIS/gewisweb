<?php

declare(strict_types=1);

namespace Frontpage\Service\Factory;

use Frontpage\Form\NewsItem as NewsItemForm;
use Frontpage\Mapper\NewsItem as NewsItemMapper;
use Frontpage\Service\AclService;
use Frontpage\Service\News as NewsService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class NewsFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): NewsService {
        return new NewsService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(NewsItemMapper::class),
            $container->get(NewsItemForm::class),
        );
    }
}
