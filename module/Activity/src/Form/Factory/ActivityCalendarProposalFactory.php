<?php

declare(strict_types=1);

namespace Activity\Form\Factory;

use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActivityCalendarProposalFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCalendarProposalForm {
        return new ActivityCalendarProposalForm(
            $container->get(MvcTranslator::class),
            $container->get(ActivityCalendarFormService::class),
            $container->get(AclService::class)->isAllowed('create_always', 'activity'),
        );
    }
}
