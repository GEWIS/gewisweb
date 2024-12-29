<?php

declare(strict_types=1);

namespace Activity\Controller\Factory;

use Activity\Controller\ActivityCalendarController;
use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
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
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get(ActivityCalendarService::class),
            $container->get(ActivityCalendarFormService::class),
            $container->get(ActivityCalendarProposalForm::class),
            $container->get('config')['calendar'],
        );
    }
}
