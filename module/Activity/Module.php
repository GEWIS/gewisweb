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
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use User\Permissions\Assertion\IsCreatorOrOrganMember;
use User\Permissions\NotAllowedException;

class Module
{
    /**
     * Get the autoloader configuration.
     */
    public function getAutoloaderConfig()
    {
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
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
                'activity_service_activity' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $entityManager = $sm->get('Doctrine\ORM\EntityManager');
                    $categoryService = $sm->get('activity_service_category');
                    $userService = $sm->get('user_service_user');
                    $organService = $sm->get('decision_service_organ');
                    $companyService = $sm->get('company_service_company');
                    $emailService = $sm->get('application_service_email');
                    $activityForm = $sm->get('activity_form_activity');
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
                'activity_service_activityQuery' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $userService = $sm->get('user_service_user');
                    $organService = $sm->get('decision_service_organ');
                    $activityMapper = $sm->get('activity_mapper_activity');
                    $proposalMapper = $sm->get('activity_mapper_proposal');
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
                'activity_service_category' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $entityManager = $sm->get('Doctrine\ORM\EntityManager');
                    $categoryMapper = $sm->get('activity_mapper_category');
                    $categoryForm = $sm->get('activity_form_category');
                    return new Service\ActivityCategory(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $categoryMapper,
                        $categoryForm
                    );
                },
                'activity_service_signupListQuery' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $signupListMapper = $sm->get('activity_mapper_signuplist');
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
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'activity_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
                'activity_form_signuplist' => function ($sm) {
                    $translator = $sm->get('translator');
                    $form = new SignupListForm($translator);
                    $form->setHydrator($sm->get('activity_hydrator'));
                    return $form;
                },
                'activity_form_signuplist_fields' => function ($sm) {
                    $translator = $sm->get('translator');
                    $form = new SignupListField($translator);
                    $form->setHydrator($sm->get('activity_hydrator'));
                    return $form;
                },
                'activity_form_activity' => function ($sm) {
                    $organService = $sm->get('decision_service_organ');
                    $organs = $organService->getEditableOrgans();
                    $companyService = $sm->get('company_service_company');
                    try {
                        $companies = $companyService->getHiddenCompanyList();
                    } catch (NotAllowedException $e) {
                        $companies = [];
                    }
                    $categoryService = $sm->get('activity_service_category');
                    $categories = $categoryService->getAllCategories();
                    $translator = $sm->get('translator');
                    $form = new Form\Activity($organs, $companies, $categories, $translator);
                    $form->setHydrator($sm->get('activity_hydrator'));
                    return $form;
                },
                'activity_form_calendar_proposal' => function ($sm) {
                    $calendarService = $sm->get('activity_service_calendar');
                    return new Form\ActivityCalendarProposal($sm->get('translator'), $calendarService);
                },
                'activity_form_calendar_option' => function ($sm) {
                    $translator = $sm->get('translator');
                    $calendarService = $sm->get('activity_service_calendar');
                    return new Form\ActivityCalendarOption($translator, $calendarService);
                },
                'activity_form_category' => function ($sm) {
                    $translator = $sm->get('translator');
                    return new CategoryForm($translator);
                },
                'activity_hydrator' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_service_signup' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $entityManager = $sm->get('Doctrine\ORM\EntityManager');
                    $userService = $sm->get('user_service_user');
                    $signupMapper = $sm->get('activity_mapper_signup');
                    $signupOptionMapper = $sm->get('activity_mapper_signup_option');
                    $signupFieldValueMapper = $sm->get('activity_mapper_signup_field_value');
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
                'activity_service_calendar' => function ($sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('activity_acl');
                    $entityManager = $sm->get('Doctrine\ORM\EntityManager');
                    $userService = $sm->get('user_service_user');
                    $organService = $sm->get('decision_service_organ');
                    $emailService = $sm->get('application_service_email');
                    $calendarOptionMapper = $sm->get('activity_mapper_calendar_option');
                    $optionProposalMapper = $sm->get('activity_mapper_option_proposal');
                    $periodMapper = $sm->get('activity_mapper_period');
                    $maxActivitiesMapper = $sm->get('activity_mapper_max_activities');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $calendarOptionForm = $sm->get('activity_form_calendar_option');
                    $calendarProposalForm = $sm->get('activity_form_calendar_proposal');
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
                'activity_mapper_activity' => function ($sm) {
                    return new Activity(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_category' => function ($sm) {
                    return new ActivityCategory(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_period' => function ($sm) {
                    return new ActivityOptionCreationPeriod(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_max_activities' => function ($sm) {
                    return new MaxActivities(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_signuplist' => function ($sm) {
                    return new SignupListMapper(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_signup_field_value' => function ($sm) {
                    return new SignupFieldValue(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_signup_option' => function ($sm) {
                    return new SignupOption(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_proposal' => function ($sm) {
                    return new Proposal(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_option_proposal' => function ($sm) {
                    return new ActivityOptionProposal(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_signup' => function ($sm) {
                    return new Signup(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_calendar_option' => function ($sm) {
                    return new ActivityCalendarOption(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_acl' => function ($sm) {
                    $acl = $sm->get('acl');
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
            ]
        ];
    }
}
