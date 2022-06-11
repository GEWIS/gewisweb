<?php

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityCalendarController;
use Psr\Container\ContainerInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ActivityCalendarControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     *
     * @return ActivityCalendarController
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
