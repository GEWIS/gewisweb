<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\AdminOptionController;
use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AdminOptionControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AdminOptionController {
        return new AdminOptionController(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ActivityCalendarService::class),
            $container->get(OrganService::class),
            $container->get(ActivityOptionCreationPeriodMapper::class),
        );
    }
}
