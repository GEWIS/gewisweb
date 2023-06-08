<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityCalendarController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActivityCalendarControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCalendarController {
        return new ActivityCalendarController(
            $container->get('activity_service_acl'),
            $container->get(MvcTranslator::class),
            $container->get('activity_service_calendar'),
            $container->get('activity_service_calendar_form'),
            $container->get('activity_form_calendar_proposal'),
            $container->get('config')['calendar'],
        );
    }
}
