<?php

declare(strict_types=1);

namespace Activity;

use Activity\Command\CalendarNotify as CalendarNotifyCommand;
use Activity\Command\DeleteOldSignups as DeleteOldSignupsCommand;
use Activity\Command\Factory\DeleteOldSignupsFactory as DeleteOldSignupsCommandFactory;
use Activity\Form\Activity as ActivityForm;
use Activity\Form\ActivityCalendarOption as ActivityCalendarOptionForm;
use Activity\Form\ActivityCalendarPeriod as ActivityCalendarPeriodForm;
use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Form\ActivityCategory as CategoryForm;
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
use Activity\Service\Activity as ActivityService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Activity\Service\ActivityCategory as ActivityCategoryService;
use Activity\Service\ActivityQuery as ActivityQueryService;
use Activity\Service\Signup as SignupService;
use Activity\Service\SignupListQuery as SignupListQueryService;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
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
                'activity_service_activity' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $categoryService = $container->get('activity_service_category');
                    $organService = $container->get('decision_service_organ');
                    $companyService = $container->get('company_service_company');
                    $emailService = $container->get('application_service_email');
                    $activityForm = $container->get('activity_form_activity');

                    return new ActivityService(
                        $aclService,
                        $translator,
                        $entityManager,
                        $categoryService,
                        $organService,
                        $companyService,
                        $emailService,
                        $activityForm,
                    );
                },
                'activity_service_calendar' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $organService = $container->get('decision_service_organ');
                    $emailService = $container->get('application_service_email');
                    $calendarOptionMapper = $container->get('activity_mapper_calendar_option');
                    $maxActivitiesMapper = $container->get('activity_mapper_max_activities');
                    $memberMapper = $container->get('decision_mapper_member');
                    $calendarPeriodForm = $container->get('activity_form_calendar_period');
                    $calendarPeriodMapper = $container->get('activity_mapper_period');
                    $calendarFormService = $container->get('activity_service_calendar_form');

                    return new ActivityCalendarService(
                        $aclService,
                        $translator,
                        $entityManager,
                        $organService,
                        $emailService,
                        $calendarOptionMapper,
                        $maxActivitiesMapper,
                        $memberMapper,
                        $calendarPeriodForm,
                        $calendarPeriodMapper,
                        $calendarFormService,
                    );
                },
                'activity_service_calendar_form' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $organService = $container->get('decision_service_organ');
                    $periodMapper = $container->get('activity_mapper_period');
                    $maxActivitiesMapper = $container->get('activity_mapper_max_activities');
                    $optionProposalMapper = $container->get('activity_mapper_option_proposal');

                    return new ActivityCalendarFormService(
                        $aclService,
                        $organService,
                        $periodMapper,
                        $maxActivitiesMapper,
                        $optionProposalMapper,
                    );
                },
                'activity_service_category' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $categoryMapper = $container->get('activity_mapper_category');
                    $categoryForm = $container->get('activity_form_category');

                    return new ActivityCategoryService(
                        $aclService,
                        $translator,
                        $categoryMapper,
                        $categoryForm,
                    );
                },
                'activity_service_activityQuery' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $organService = $container->get('decision_service_organ');
                    $activityMapper = $container->get('activity_mapper_activity');
                    $proposalMapper = $container->get('activity_mapper_proposal');

                    return new ActivityQueryService(
                        $aclService,
                        $translator,
                        $organService,
                        $activityMapper,
                        $proposalMapper,
                    );
                },
                'activity_service_signup' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $signupMapper = $container->get('activity_mapper_signup');
                    $signupOptionMapper = $container->get('activity_mapper_signup_option');
                    $signupFieldValueMapper = $container->get('activity_mapper_signup_field_value');

                    return new SignupService(
                        $aclService,
                        $translator,
                        $entityManager,
                        $signupMapper,
                        $signupFieldValueMapper,
                        $signupOptionMapper,
                    );
                },
                'activity_service_signupListQuery' => static function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $signupListMapper = $container->get('activity_mapper_signuplist');

                    return new SignupListQueryService(
                        $aclService,
                        $translator,
                        $signupListMapper,
                    );
                },
                'activity_form_activity_signup' => static function () {
                    return new SignupForm();
                },
                'activity_form_signuplist' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $form = new SignupListForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_signuplist_fields' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $form = new SignupListFieldForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_activity' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $form = new ActivityForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_calendar_proposal' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $calendarFormService = $container->get('activity_service_calendar_form');
                    $aclService = $container->get('activity_service_acl');
                    $createAlways = $aclService->isAllowed('create_always', 'activity');

                    return new ActivityCalendarProposalForm($translator, $calendarFormService, $createAlways);
                },
                'activity_form_calendar_option' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);
                    $calendarFormService = $container->get('activity_service_calendar_form');

                    return new ActivityCalendarOptionForm($translator, $calendarFormService);
                },
                'activity_form_calendar_period' => static function (ContainerInterface $container) {
                    return new ActivityCalendarPeriodForm($container->get(MvcTranslator::class));
                },
                'activity_form_category' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);

                    return new CategoryForm($translator);
                },
                'activity_hydrator' => static function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_activity' => static function (ContainerInterface $container) {
                    return new ActivityMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_category' => static function (ContainerInterface $container) {
                    return new ActivityCategoryMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_period' => static function (ContainerInterface $container) {
                    return new ActivityOptionCreationPeriodMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_max_activities' => static function (ContainerInterface $container) {
                    return new MaxActivitiesMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_signuplist' => static function (ContainerInterface $container) {
                    return new SignupListMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_signup_field_value' => static function (ContainerInterface $container) {
                    return new SignupFieldValueMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_signup_option' => static function (ContainerInterface $container) {
                    return new SignupOptionMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_proposal' => static function (ContainerInterface $container) {
                    return new ProposalMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_option_proposal' => static function (ContainerInterface $container) {
                    return new ActivityOptionProposalMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_signup' => static function (ContainerInterface $container) {
                    return new SignupMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_mapper_calendar_option' => static function (ContainerInterface $container) {
                    return new ActivityCalendarOptionMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'activity_service_acl' => AclServiceFactory::class,
                CalendarNotifyCommand::class => static function (ContainerInterface $container) {
                    $calendarNotify = new CalendarNotifyCommand();
                    $calendarService = $container->get('activity_service_calendar');
                    $calendarNotify->setCalendarService($calendarService);

                    return $calendarNotify;
                },
                DeleteOldSignupsCommand::class => DeleteOldSignupsCommandFactory::class,
            ],
        ];
    }
}
