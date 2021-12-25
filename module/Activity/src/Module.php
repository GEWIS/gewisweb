<?php

namespace Activity;

use Activity\Command\CalendarNotify;
use Activity\Form\{
    Activity as ActivityForm,
    ActivityCalendarOption as ActivityCalendarOptionForm,
    ActivityCalendarPeriod as ActivityCalendarPeriodForm,
    ActivityCalendarProposal as ActivityCalendarProposalForm,
    ActivityCategory as CategoryForm,
    Signup as SignupForm,
    SignupList as SignupListForm,
    SignupListField as SignupListFieldForm,
};
use Activity\Mapper\{
    Activity as ActivityMapper,
    ActivityCalendarOption as ActivityCalendarOptionMapper,
    ActivityCategory as ActivityCategoryMapper,
    ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper,
    ActivityOptionProposal as ActivityOptionProposalMapper,
    MaxActivities as MaxActivitiesMapper,
    Proposal as ProposalMapper,
    Signup as SignupMapper,
    SignupFieldValue as SignupFieldValueMapper,
    SignupList as SignupListMapper,
    SignupOption as SignupOptionMapper,
};
use Activity\Service\{
    Activity as ActivityService,
    ActivityCalendar as ActivityCalendarService,
    ActivityCalendarForm as ActivityCalendarFormService,
    ActivityCategory as ActivityCategoryService,
    ActivityQuery as ActivityQueryService,
    Signup as SignupService,
    SignupListQuery as SignupListQueryService,
};
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Interop\Container\ContainerInterface;
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
                'activity_service_activity' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
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
                'activity_service_calendar' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $organService = $container->get('decision_service_organ');
                    $emailService = $container->get('application_service_email');
                    $calendarOptionMapper = $container->get('activity_mapper_calendar_option');
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
                        $memberMapper,
                        $calendarPeriodForm,
                        $calendarPeriodMapper,
                        $calendarFormService,
                    );
                },
                'activity_service_calendar_form' => function (ContainerInterface $container) {
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
                'activity_service_category' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
                    $categoryMapper = $container->get('activity_mapper_category');
                    $categoryForm = $container->get('activity_form_category');

                    return new ActivityCategoryService(
                        $aclService,
                        $translator,
                        $categoryMapper,
                        $categoryForm,
                    );
                },
                'activity_service_activityQuery' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
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
                'activity_service_signup' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
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
                'activity_service_signupListQuery' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $translator = $container->get('translator');
                    $signupListMapper = $container->get('activity_mapper_signuplist');

                    return new SignupListQueryService(
                        $aclService,
                        $translator,
                        $signupListMapper,
                    );
                },
                'activity_form_activity_signup' => function () {
                    return new SignupForm();
                },
                'activity_form_signuplist' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $form = new SignupListForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_signuplist_fields' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $form = new SignupListFieldForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_activity' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $form = new ActivityForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_calendar_proposal' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $calendarFormService = $container->get('activity_service_calendar_form');
                    $aclService = $container->get('activity_service_acl');
                    $createAlways = $aclService->isAllowed('create_always', 'activity');
                    return new ActivityCalendarProposalForm($translator, $calendarFormService, $createAlways);
                },
                'activity_form_calendar_option' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $calendarFormService = $container->get('activity_service_calendar_form');

                    return new ActivityCalendarOptionForm($translator, $calendarFormService);
                },
                'activity_form_calendar_period' => function (ContainerInterface $container) {
                    return new ActivityCalendarPeriodForm($container->get('translator'));
                },
                'activity_form_category' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');

                    return new CategoryForm($translator);
                },
                'activity_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_activity' => function (ContainerInterface $container) {
                    return new ActivityMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_category' => function (ContainerInterface $container) {
                    return new ActivityCategoryMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_period' => function (ContainerInterface $container) {
                    return new ActivityOptionCreationPeriodMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_max_activities' => function (ContainerInterface $container) {
                    return new MaxActivitiesMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signuplist' => function (ContainerInterface $container) {
                    return new SignupListMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup_field_value' => function (ContainerInterface $container) {
                    return new SignupFieldValueMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup_option' => function (ContainerInterface $container) {
                    return new SignupOptionMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_proposal' => function (ContainerInterface $container) {
                    return new ProposalMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_option_proposal' => function (ContainerInterface $container) {
                    return new ActivityOptionProposalMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup' => function (ContainerInterface $container) {
                    return new SignupMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_calendar_option' => function (ContainerInterface $container) {
                    return new ActivityCalendarOptionMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_service_acl' => AclServiceFactory::class,
                CalendarNotify::class => function (ContainerInterface $container) {
                    $calendarNotify = new CalendarNotify();
                    $calendarService = $container->get('activity_service_calendar');
                    $calendarNotify->setCalendarService($calendarService);
                    return $calendarNotify;
                },
            ],
        ];
    }
}
