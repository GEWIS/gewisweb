<?php

declare(strict_types=1);

namespace Activity\Service\Factory;

use Activity\Form\Activity as ActivityForm;
use Activity\Service\AclService;
use Activity\Service\Activity as ActivityService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Application\Service\Email as EmailService;
use Company\Service\Company as CompanyService;
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class ActivityFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): ActivityService {
        return new ActivityService(
            $container->get(AclService::class),
            $container->get(MvcTranslator::class),
            $container->get('doctrine.entitymanager.orm_default'),
            $container->get(ActivityCategoryService::class),
            $container->get(OrganService::class),
            $container->get(CompanyService::class),
            $container->get(EmailService::class),
            $container->get(ActivityForm::class),
        );
    }
}
