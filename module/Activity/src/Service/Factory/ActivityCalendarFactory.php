<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Form\ActivityCalendarPeriod as ActivityCalendarPeriodForm;
use Activity\Mapper\ActivityCalendarOption as ActivityCalendarOptionMapper;
use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Mapper\MaxActivities as MaxActivitiesMapper;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Application\Service\Email as EmailService;
use Decision\Mapper\Member as MemberMapper;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ActivityCalendarFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCalendarService {
        return new ActivityCalendarService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get('doctrine.entitymanager.orm_default'),
            $container->get(OrganService::class),
            $container->get(EmailService::class),
            $container->get(ActivityCalendarOptionMapper::class),
            $container->get(MaxActivitiesMapper::class),
            $container->get(MemberMapper::class),
            $container->get(ActivityCalendarPeriodForm::class),
            $container->get(ActivityOptionCreationPeriodMapper::class),
            $container->get(ActivityCalendarFormService::class),
        );
    }
}
