<?php

namespace Activity;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Form\SignupList as SignupListForm;
use Activity\Form\SignupListField;
use Activity\Mapper\Activity;
use Activity\Mapper\ActivityCalendarOption;
use Activity\Mapper\ActivityCategory;
use Activity\Mapper\ActivityOptionCreationPeriod;
use Activity\Mapper\ActivityOptionProposal;
use Activity\Mapper\MaxActivities;
use Activity\Mapper\Proposal;
use Activity\Mapper\Signup;
use Activity\Mapper\SignupFieldValue;
use Activity\Mapper\SignupList as SignupListMapper;
use Activity\Mapper\SignupOption;
use Activity\Service\ActivityQuery;
use Activity\Service\SignupListQuery;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Interop\Container\ContainerInterface;
use User\Permissions\Assertion\IsCreatorOrOrganMember;
use User\Permissions\NotAllowedException;

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
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'activity_service_activity' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $categoryService = $container->get('activity_service_category');
                    $userService = $container->get('user_service_user');
                    $organService = $container->get('decision_service_organ');
                    $companyService = $container->get('company_service_company');
                    $emailService = $container->get('application_service_email');
                    $activityForm = $container->get('activity_form_activity');

                    return new Service\Activity(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $categoryService,
                        $userService,
                        $organService,
                        $companyService,
                        $emailService,
                        $activityForm
                    );
                },
                'activity_service_activityQuery' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $userService = $container->get('user_service_user');
                    $organService = $container->get('decision_service_organ');
                    $activityMapper = $container->get('activity_mapper_activity');
                    $proposalMapper = $container->get('activity_mapper_proposal');

                    return new ActivityQuery(
                        $translator,
                        $userRole,
                        $acl,
                        $userService,
                        $organService,
                        $activityMapper,
                        $proposalMapper
                    );
                },
                'activity_service_category' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $categoryMapper = $container->get('activity_mapper_category');
                    $categoryForm = $container->get('activity_form_category');

                    return new Service\ActivityCategory(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $categoryMapper,
                        $categoryForm
                    );
                },
                'activity_service_signupListQuery' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $signupListMapper = $container->get('activity_mapper_signuplist');

                    return new SignupListQuery(
                        $translator,
                        $userRole,
                        $acl,
                        $signupListMapper
                    );
                },
                'activity_form_activity_signup' => function () {
                    return new Form\Signup();
                },
                'activity_form_signuplist' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $form = new SignupListForm($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_signuplist_fields' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $form = new SignupListField($translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_activity' => function (ContainerInterface $container) {
                    $organService = $container->get('decision_service_organ');
                    try {
                        $organs = $organService->getEditableOrgans();
                    } catch (NotAllowedException $e) {
                        $organs = [];
                    }
                    $organs = $organService->getEditableOrgans();
                    $companyService = $container->get('company_service_company');
                    try {
                        $companies = $companyService->getHiddenCompanyList();
                    } catch (NotAllowedException $e) {
                        $companies = [];
                    }
                    $categoryService = $container->get('activity_service_category');
                    $categories = $categoryService->getAllCategories();
                    $translator = $container->get('translator');
                    $form = new Form\Activity($organs, $companies, $categories, $translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_calendar_proposal' => function (ContainerInterface $container) {
                    $calendarService = $container->get('activity_service_calendar');

                    return new Form\ActivityCalendarProposal($container->get('translator'), $calendarService);
                },
                'activity_form_calendar_option' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $calendarService = $container->get('activity_service_calendar');

                    return new Form\ActivityCalendarOption($translator, $calendarService);
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
                'activity_service_signup' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $userService = $container->get('user_service_user');
                    $signupMapper = $container->get('activity_mapper_signup');
                    $signupOptionMapper = $container->get('activity_mapper_signup_option');
                    $signupFieldValueMapper = $container->get('activity_mapper_signup_field_value');

                    return new Service\Signup(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $userService,
                        $signupMapper,
                        $signupOptionMapper,
                        $signupFieldValueMapper
                    );
                },
                'activity_service_calendar' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('activity_acl');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $userService = $container->get('user_service_user');
                    $organService = $container->get('decision_service_organ');
                    $emailService = $container->get('application_service_email');
                    $calendarOptionMapper = $container->get('activity_mapper_calendar_option');
                    $optionProposalMapper = $container->get('activity_mapper_option_proposal');
                    $periodMapper = $container->get('activity_mapper_period');
                    $maxActivitiesMapper = $container->get('activity_mapper_max_activities');
                    $memberMapper = $container->get('decision_mapper_member');
                    $calendarOptionForm = $container->get('activity_form_calendar_option');
                    $calendarProposalForm = $container->get('activity_form_calendar_proposal');

                    return new Service\ActivityCalendar(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $userService,
                        $organService,
                        $emailService,
                        $calendarOptionMapper,
                        $optionProposalMapper,
                        $periodMapper,
                        $maxActivitiesMapper,
                        $memberMapper,
                        $calendarOptionForm,
                        $calendarProposalForm
                    );
                },
                'activity_mapper_activity' => function (ContainerInterface $container) {
                    return new Activity(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_category' => function (ContainerInterface $container) {
                    return new ActivityCategory(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_period' => function (ContainerInterface $container) {
                    return new ActivityOptionCreationPeriod(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_max_activities' => function (ContainerInterface $container) {
                    return new MaxActivities(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signuplist' => function (ContainerInterface $container) {
                    return new SignupListMapper(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup_field_value' => function (ContainerInterface $container) {
                    return new SignupFieldValue(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup_option' => function (ContainerInterface $container) {
                    return new SignupOption(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_proposal' => function (ContainerInterface $container) {
                    return new Proposal(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_option_proposal' => function (ContainerInterface $container) {
                    return new ActivityOptionProposal(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_signup' => function (ContainerInterface $container) {
                    return new Signup(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_mapper_calendar_option' => function (ContainerInterface $container) {
                    return new ActivityCalendarOption(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'activity_acl' => function (ContainerInterface $container) {
                    $acl = $container->get('acl');
                    $acl->addResource('activity');
                    $acl->addResource('activityApi');
                    $acl->addResource('myActivities');
                    $acl->addResource('model');
                    $acl->addResource('activity_calendar_proposal');
                    $acl->addResource('signupList');

                    $acl->allow('guest', 'activity', ['view', 'viewCategory']);
                    $acl->allow('guest', 'signupList', ['view', 'externalSignup']);

                    $acl->allow('user', 'activity_calendar_proposal', ['create', 'delete_own']);
                    $acl->allow('admin', 'activity_calendar_proposal', ['create_always', 'delete_all', 'approve']);

                    $acl->allow('user', 'myActivities', 'view');
                    $acl->allow(
                        'user',
                        'signupList',
                        ['view', 'viewDetails', 'signup', 'signoff', 'checkUserSignedUp']
                    );

                    $acl->allow('active_member', 'activity', ['create', 'viewAdmin', 'listCategories']);
                    $acl->allow(
                        'active_member',
                        'activity',
                        ['update', 'viewDetails', 'adminSignup', 'viewParticipants', 'exportParticipants'],
                        new IsCreatorOrOrganMember()
                    );
                    $acl->allow(
                        'active_member',
                        'signupList',
                        ['adminSignup', 'viewParticipants', 'exportParticipants'],
                        new IsCreatorOrOrganMember()
                    );

                    $acl->allow('sosuser', 'signupList', ['signup', 'signoff', 'checkUserSignedUp']);

                    $acl->allow('user', 'activityApi', 'list');
                    $acl->allow('apiuser', 'activityApi', 'list');

                    return $acl;
                },
            ],
        ];
    }
}
