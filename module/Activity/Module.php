<?php

namespace Activity;

use Activity\Form\ActivityCategory as CategoryForm;
use Activity\Form\SignupList as SignupListForm;
use Activity\Form\SignupListFields;
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
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use User\Permissions\Assertion\IsCreatorOrOrganMember;
use User\Permissions\NotAllowedException;

class Module
{
    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        if (APP_ENV === 'production') {
            return [
                'Zend\Loader\ClassMapAutoloader' => [
                    __DIR__ . '/autoload_classmap.php',
                ]
            ];
        }

        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
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
            'invokables' => [
                'activity_service_activity' => 'Activity\Service\Activity',
                'activity_service_activityQuery' => 'Activity\Service\ActivityQuery',
                'activity_service_category' => 'Activity\Service\ActivityCategory',
                'activity_service_signupListQuery' => 'Activity\Service\SignupListQuery',
                'activity_form_activity_signup' => 'Activity\Form\ActivitySignup'
            ],
            'factories' => [
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
                    $form = new SignupListFields();
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
                    $form = new Form\ActivityCalendarProposal($sm->get('translator'), $calendarService);
                    return $form;
                },
                'activity_form_calendar_option' => function ($sm) {
                    $translator = $sm->get('translator');
                    $calendarService = $sm->get('activity_service_calendar');
                    $form = new Form\ActivityCalendarOption($translator, $calendarService);
                    return $form;
                },
                'activity_form_category' => function ($sm) {
                    $translator = $sm->get('translator');
                    $form = new CategoryForm($translator);

                    return $form;
                },
                'activity_hydrator' => function ($sm) {
                    return new DoctrineObject(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_service_signup' => function ($sm) {
                    $ac = new Service\Signup();
                    $ac->setServiceManager($sm);

                    return $ac;
                },
                'activity_service_signoff' => function ($sm) {
                    $ac = new Service\Signup();
                    $ac->setServiceManager($sm);

                    return $ac;
                },
                'activity_service_calendar' => function ($sm) {
                    $ac = new Service\ActivityCalendar();
                    $ac->setServiceManager($sm);

                    return $ac;
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
                    $acl->addResource('myActivities');
                    $acl->addResource('model');
                    $acl->addResource('activity_calendar_proposal');
                    $acl->addResource('signupList');

                    $acl->allow('guest', 'activity', ['view', 'viewCategory']);
                    $acl->allow('guest', 'signupList', ['view', 'externalSignup']);

                    $acl->allow('user', 'activity_calendar_proposal', ['create', 'delete_own']);
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
                    return $acl;
                },
            ]
        ];
    }
}
