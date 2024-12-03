<?php

declare(strict_types=1);

namespace Activity;

use Activity\Command\CalendarNotify as CalendarNotifyCommand;
use Activity\Command\DeleteOldSignups as DeleteOldSignupsCommand;
use Activity\Command\Factory\CalendarNotifyFactory as CalendarNotifyCommandFactory;
use Activity\Command\Factory\DeleteOldSignupsFactory as DeleteOldSignupsCommandFactory;
use Activity\Form\Activity as ActivityForm;
use Activity\Form\ActivityCalendarOption as ActivityCalendarOptionForm;
use Activity\Form\ActivityCalendarPeriod as ActivityCalendarPeriodForm;
use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Form\Factory\ActivityCalendarOptionFactory as ActivityCalendarOptionFormFactory;
use Activity\Form\Factory\ActivityCalendarProposalFactory as ActivityCalendarProposalFormFactory;
use Activity\Form\Factory\ActivityFactory as ActivityFormFactory;
use Activity\Form\Factory\SignupListFactory as SignupListFormFactory;
use Activity\Form\Factory\SignupListFieldFactory as SignupListFieldFormFactory;
use Activity\Form\Signup as SignupForm;
use Activity\Form\SignupList as SignupListForm;
use Activity\Form\SignupListField as SignupListFieldForm;
use Activity\Mapper\Activity as ActivityMapper;
use Activity\Mapper\ActivityCalendarOption as ActivityCalendarOptionMapper;
use Activity\Mapper\ActivityCategory as ActivityCategoryMapper;
use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Mapper\ActivityOptionProposal as ActivityOptionProposalMapper;
use Activity\Mapper\MaxActivities as MaxActivitiesMapper;
use Activity\Mapper\Proposal as ProposalMapper;
use Activity\Mapper\Signup as SignupMapper;
use Activity\Mapper\SignupFieldValue as SignupFieldValueMapper;
use Activity\Mapper\SignupList as SignupListMapper;
use Activity\Mapper\SignupOption as SignupOptionMapper;
use Activity\Service\AclService;
use Activity\Service\Activity as ActivityService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Activity\Service\Factory\ActivityCalendarFactory as ActivityCalendarServiceFactory;
use Activity\Service\Factory\ActivityCalendarFormFactory as ActivityCalendarFormServiceFactory;
use Activity\Service\Factory\ActivityCategoryFactory as ActivityCategoryServiceFactory;
use Activity\Service\Factory\ActivityFactory as ActivityServiceFactory;
use Activity\Service\Factory\ActivityQueryFactory as ActivityQueryServiceFactory;
use Activity\Service\Factory\SignupFactory as SignupServiceFactory;
use Activity\Service\Factory\SignupListQueryFactory as SignupListQueryServiceFactory;
use Activity\Service\Signup as SignupService;
use Activity\Service\SignupListQuery as SignupListQueryService;
use Application\Form\Factory\BaseFormFactory;
use Application\Mapper\Factory\BaseMapperFactory;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Psr\Container\ContainerInterface;
use User\Authorization\AclServiceFactory;

class Module
{
    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        return [
            'factories' => [
                // Services
                AclService::class => AclServiceFactory::class,
                ActivityService::class => ActivityServiceFactory::class,
                ActivityCalendarService::class => ActivityCalendarServiceFactory::class,
                ActivityCalendarFormService::class => ActivityCalendarFormServiceFactory::class,
                ActivityCategoryService::class => ActivityCategoryServiceFactory::class,
                ActivityQueryService::class => ActivityQueryServiceFactory::class,
                SignupService::class => SignupServiceFactory::class,
                SignupListQueryService::class => SignupListQueryServiceFactory::class,
                // Mappers
                ActivityMapper::class => BaseMapperFactory::class,
                ActivityCategoryMapper::class => BaseMapperFactory::class,
                ActivityOptionCreationPeriodMapper::class => BaseMapperFactory::class,
                MaxActivitiesMapper::class => BaseMapperFactory::class,
                SignupListMapper::class => BaseMapperFactory::class,
                SignupFieldValueMapper::class => BaseMapperFactory::class,
                SignupOptionMapper::class => BaseMapperFactory::class,
                ProposalMapper::class => BaseMapperFactory::class,
                ActivityOptionProposalMapper::class => BaseMapperFactory::class,
                SignupMapper::class => BaseMapperFactory::class,
                ActivityCalendarOptionMapper::class => BaseMapperFactory::class,
                // Forms
                'activity_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                SignupForm::class => InvokableFactory::class,
                SignupListForm::class => SignupListFormFactory::class,
                SignupListFieldForm::class => SignupListFieldFormFactory::class,
                ActivityForm::class => ActivityFormFactory::class,
                ActivityCalendarProposalForm::class => ActivityCalendarProposalFormFactory::class,
                ActivityCalendarOptionForm::class => ActivityCalendarOptionFormFactory::class,
                ActivityCalendarPeriodForm::class => BaseFormFactory::class,
                CategoryForm::class => BaseFormFactory::class,
                // Commands
                CalendarNotifyCommand::class => CalendarNotifyCommandFactory::class,
                DeleteOldSignupsCommand::class => DeleteOldSignupsCommandFactory::class,
            ],
        ];
    }
}
