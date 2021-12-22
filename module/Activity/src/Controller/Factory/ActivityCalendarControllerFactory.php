<?php

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityCalendarController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ActivityCalendarControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return ActivityCalendarController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): ActivityCalendarController {
        return new ActivityCalendarController(
            $container->get('activity_service_acl'),
            $container->get('translator'),
            $container->get('activity_service_calendar'),
            $container->get('activity_service_calendar_form'),
            $container->get('activity_form_calendar_proposal'),
            $container->get('config')['calendar'],
        );
    }
}
