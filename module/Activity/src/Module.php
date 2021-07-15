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
use User\Authorization\AclServiceFactory;
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
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $categoryService = $container->get('activity_service_category');
                    $organService = $container->get('decision_service_organ');
                    $companyService = $container->get('company_service_company');
                    $emailService = $container->get('application_service_email');
                    $activityForm = $container->get('activity_form_activity');
                    $aclService = $container->get('activity_service_acl');

                    return new Service\Activity(
                        $translator,
                        $entityManager,
                        $categoryService,
                        $organService,
                        $companyService,
                        $emailService,
                        $activityForm,
                        $aclService
                    );
                },
                'activity_service_activityQuery' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $organService = $container->get('decision_service_organ');
                    $activityMapper = $container->get('activity_mapper_activity');
                    $proposalMapper = $container->get('activity_mapper_proposal');
                    $aclService = $container->get('activity_service_acl');

                    return new ActivityQuery(
                        $translator,
                        $organService,
                        $activityMapper,
                        $proposalMapper,
                        $aclService
                    );
                },
                'activity_service_category' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $categoryMapper = $container->get('activity_mapper_category');
                    $categoryForm = $container->get('activity_form_category');
                    $aclService = $container->get('activity_service_acl');

                    return new Service\ActivityCategory(
                        $translator,
                        $entityManager,
                        $categoryMapper,
                        $categoryForm,
                        $aclService
                    );
                },
                'activity_service_signupListQuery' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $signupListMapper = $container->get('activity_mapper_signuplist');
                    $aclService = $container->get('activity_service_acl');

                    return new SignupListQuery(
                        $translator,
                        $signupListMapper,
                        $aclService
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

                    $companyService = $container->get('company_service_company');
                    try {
                        $companies = $companyService->getHiddenCompanyList();
                    } catch (NotAllowedException $e) {
                        $companies = [];
                    }

                    $categoryService = $container->get('activity_service_category');
                    try {
                        $categories = $categoryService->getAllCategories();
                    } catch (NotAllowedException $e) {
                        $categories = [];
                    }

                    $translator = $container->get('translator');
                    $form = new Form\Activity($organs, $companies, $categories, $translator);
                    $form->setHydrator($container->get('activity_hydrator'));

                    return $form;
                },
                'activity_form_calendar_proposal' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $calendarFormService = $container->get('activity_service_calendar_form');
                    $aclService = $container->get('activity_service_acl');
                    $createAlways = $aclService->isAllowed('create_always');
                    return new Form\ActivityCalendarProposal($translator, $calendarFormService, $createAlways);
                },
                'activity_form_calendar_option' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $calendarFormService = $container->get('activity_service_calendar_form');

                    return new Form\ActivityCalendarOption($translator, $calendarFormService);
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
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $signupMapper = $container->get('activity_mapper_signup');
                    $signupOptionMapper = $container->get('activity_mapper_signup_option');
                    $signupFieldValueMapper = $container->get('activity_mapper_signup_field_value');
                    $aclService = $container->get('activity_service_acl');

                    return new Service\Signup(
                        $translator,
                        $entityManager,
                        $signupMapper,
                        $signupOptionMapper,
                        $signupFieldValueMapper,
                        $aclService
                    );
                },
                'activity_service_calendar' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $organService = $container->get('decision_service_organ');
                    $emailService = $container->get('application_service_email');
                    $calendarOptionMapper = $container->get('activity_mapper_calendar_option');
                    $memberMapper = $container->get('decision_mapper_member');
                    $calendarOptionForm = $container->get('activity_form_calendar_option');
                    $calendarProposalForm = $container->get('activity_form_calendar_proposal');
                    $aclService = $container->get('activity_service_acl');
                    $calendarFormService = $container->get('activity_service_calendar_form');

                    return new Service\ActivityCalendar(
                        $translator,
                        $entityManager,
                        $organService,
                        $emailService,
                        $calendarOptionMapper,
                        $memberMapper,
                        $calendarOptionForm,
                        $calendarProposalForm,
                        $aclService,
                        $calendarFormService
                    );
                },
                'activity_service_calendar_form' => function (ContainerInterface $container) {
                    $aclService = $container->get('activity_service_acl');
                    $organService = $container->get('decision_service_organ');
                    $periodMapper = $container->get('activity_mapper_period');
                    $maxActivitiesMapper = $container->get('activity_mapper_max_activities');
                    $optionProposalMapper = $container->get('activity_mapper_option_proposal');

                    return new Service\ActivityCalendarForm(
                        $aclService,
                        $organService,
                        $periodMapper,
                        $maxActivitiesMapper,
                        $optionProposalMapper
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
                'activity_service_acl' => AclServiceFactory::class,
            ],
        ];
    }
}
