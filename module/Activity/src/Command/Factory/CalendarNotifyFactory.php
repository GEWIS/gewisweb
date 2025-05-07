<?php

declare(strict_types=1);

namespace Activity\Command\Factory;

use Activity\Command\CalendarNotify;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class CalendarNotifyFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): CalendarNotify {
        return new CalendarNotify($container->get(ActivityCalendarService::class));
    }
}
