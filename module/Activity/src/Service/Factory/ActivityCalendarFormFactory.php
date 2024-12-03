<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Mapper\ActivityOptionProposal as ActivityOptionProposalMapper;
use Activity\Mapper\MaxActivities as MaxActivitiesMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Decision\Service\Organ as OrganService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActivityCalendarFormFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCalendarFormService {
        return new ActivityCalendarFormService(
            $container->get(AclService::class),
            $container->get(OrganService::class),
            $container->get(ActivityOptionCreationPeriodMapper::class),
            $container->get(MaxActivitiesMapper::class),
            $container->get(ActivityOptionProposalMapper::class),
        );
    }
}
