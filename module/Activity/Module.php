<?php

namespace Activity;

use Activity\Form\ActivityFieldFieldSet;
use Activity\Mapper\Activity;
use Activity\Mapper\ActivityCalendarOption;
use Activity\Mapper\ActivityFieldValue;
use Activity\Mapper\ActivityOption;
use Activity\Mapper\ActivityOptionCreationPeriod;
use Activity\Mapper\ActivityOptionProposal;
use Activity\Mapper\MaxActivities;
use Activity\Mapper\Proposal;
use Activity\Mapper\Signup;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;
use User\Permissions\Assertion\IsCreator;
use User\Permissions\Assertion\IsOrganMember;

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
                'activity_service_activityTranslator' => 'Activity\Service\ActivityTranslator',
                'activity_form_activity_signup' => 'Activity\Form\ActivitySignup'
            ],
            'factories' => [
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'activity_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
                'activity_form_activityfield_fieldset' => function ($sm) {
                    $form = new ActivityFieldFieldSet();
                    $form->setHydrator($sm->get('activity_hydrator'));
                    return $form;
                },
                'activity_form_activity' => function ($sm) {
                    $organService = $sm->get('decision_service_organ');
                    $organs = $organService->getEditableOrgans();
                    $translator = $sm->get('translator');
                    $form = new \Activity\Form\Activity($organs, $translator, $sm->get('activity_doctrine_em'));
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
                'activity_mapper_activity_field_value' => function ($sm) {
                    return new ActivityFieldValue(
                        $sm->get('activity_doctrine_em')
                    );
                },
                'activity_mapper_activity_option' => function ($sm) {
                    return new ActivityOption(
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
                    $acl->addResource('activitySignup');
                    $acl->addResource('model');
                    $acl->addResource('activity_calendar_proposal');

                    $acl->allow('guest', 'activity', 'view');

                    $acl->allow('guest', 'activitySignup', 'externalSignup');

                    $acl->allow('user', 'activity', 'create');
                    $acl->allow('user', 'myActivities', 'view');
                    $acl->allow('user', 'activitySignup', ['view', 'signup', 'signoff', 'checkUserSignedUp']);

                    $acl->allow('admin', 'activity', ['update', 'viewDetails', 'adminSignup']);
                    $acl->allow('user', 'activity', ['update', 'viewDetails', 'adminSignup'], new IsCreator());
                    $acl->allow(
                        'active_member',
                        'activity',
                        ['update', 'viewDetails', 'adminSignup'],
                        new IsOrganMember()
                    );

                    $acl->allow('sosuser', 'activitySignup', ['signup', 'signoff', 'checkUserSignedUp']);

                    $acl->allow('user', 'activityApi', 'list');
                    $acl->allow('apiuser', 'activityApi', 'list');

                    $acl->allow('user', 'activity_calendar_proposal', ['create', 'delete_own']);
                    $acl->allow('admin', 'activity_calendar_proposal', ['create_always', 'delete_all', 'approve']);
                    return $acl;
                },
            ]
        ];
    }
}
