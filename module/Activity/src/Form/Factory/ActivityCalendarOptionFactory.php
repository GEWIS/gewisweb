<?php

declare(strict_types=1);

namespace Activity\Form\Factory;

use Activity\Form\ActivityCalendarOption as ActivityCalendarOptionForm;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class ActivityCalendarOptionFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityCalendarOptionForm {
        return new ActivityCalendarOptionForm(
            $container->get(MvcTranslator::class),
            $container->get(ActivityCalendarFormService::class),
        );
    }
}
