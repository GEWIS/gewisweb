<?php

declare(strict_types=1);

namespace Frontpage\Controller\Factory;

use Frontpage\Controller\PollController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PollControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return PollController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): PollController {
        return new PollController(
            $container->get('frontpage_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('frontpage_form_poll_comment'),
            $container->get('frontpage_service_poll'),
        );
    }
}
