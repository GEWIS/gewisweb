<?php

namespace Decision;

use Decision\Controller\FileBrowser\LocalFileReader;
use Decision\Form\Authorization;
use Decision\Form\Document;
use Decision\Form\Notes;
use Decision\Form\OrganInformation;
use Decision\Form\ReorderDocument;
use Decision\Form\SearchDecision;
use Decision\Mapper\Decision;
use Decision\Mapper\Meeting;
use Decision\Mapper\Member;
use Decision\Mapper\Organ;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\ServiceManager\ServiceLocatorInterface;

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
        return include __DIR__.'/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     * @noinspection PhpParamsInspection
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'decision_service_organ' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('decision_acl');
                    $entityManager = $sm->get('doctrine.entitymanager.orm_default');
                    $userService = $sm->get('user_service_user');
                    $storageService = $sm->get('application_service_storage');
                    $emailService = $sm->get('application_service_email');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $organMapper = $sm->get('decision_mapper_organ');
                    $organInformationForm = $sm->get('decision_form_organ_information');
                    $organInformationConfig = $sm->get('config')['organ_information'];

                    return new Service\Organ(
                        $translator,
                        $userRole,
                        $acl,
                        $entityManager,
                        $userService,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $organMapper,
                        $organInformationForm,
                        $organInformationConfig
                    );
                },
                'decision_service_decision' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('decision_acl');
                    $userService = $sm->get('user_service_user');
                    $storageService = $sm->get('application_service_storage');
                    $emailService = $sm->get('application_service_email');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $meetingMapper = $sm->get('decision_mapper_meeting');
                    $decisionMapper = $sm->get('decision_mapper_decision');
                    $authorizationMapper = $sm->get('decision_mapper_authorization');
                    $notesForm = $sm->get('decision_form_notes');
                    $documentForm = $sm->get('decision_form_document');
                    $reorderDocumentForm = $sm->get('decision_form_reorder_document');
                    $searchDecisionForm = $sm->get('decision_form_searchdecision');
                    $authorizationForm = $sm->get('decision_form_authorization');

                    return new Service\Decision(
                        $translator,
                        $userRole,
                        $acl,
                        $userService,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $meetingMapper,
                        $decisionMapper,
                        $authorizationMapper,
                        $notesForm,
                        $documentForm,
                        $reorderDocumentForm,
                        $searchDecisionForm,
                        $authorizationForm
                    );
                },
                'decision_service_member' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('decision_acl');
                    $userService = $sm->get('user_service_user');
                    $memberMapper = $sm->get('decision_mapper_member');
                    $authorizationMapper = $sm->get('decision_mapper_authorization');
                    $config = $sm->get('config');

                    return new Service\Member(
                        $translator,
                        $userRole,
                        $acl,
                        $userService,
                        $memberMapper,
                        $authorizationMapper,
                        $config
                    );
                },
                'decision_service_memberinfo' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');
                    $userRole = $sm->get('user_role');
                    $acl = $sm->get('decision_acl');
                    $photoService = $sm->get('photo_service_photo');
                    $memberMapper = $sm->get('decision_mapper_member');

                    return new Service\MemberInfo(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $memberMapper
                    );
                },
                'decision_mapper_member' => function (ServiceLocatorInterface $sm) {
                    return new Member(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_organ' => function (ServiceLocatorInterface $sm) {
                    return new Organ(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_meeting' => function (ServiceLocatorInterface $sm) {
                    return new Meeting(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_decision' => function (ServiceLocatorInterface $sm) {
                    return new Decision(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_authorization' => function (ServiceLocatorInterface $sm) {
                    return new Mapper\Authorization(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_form_searchdecision' => function (ServiceLocatorInterface $sm) {
                    return new SearchDecision(
                        $sm->get('translator')
                    );
                },
                'decision_form_document' => function (ServiceLocatorInterface $sm) {
                    return new Document(
                        $sm->get('translator')
                    );
                },
                'decision_form_notes' => function (ServiceLocatorInterface $sm) {
                    return new Notes(
                        $sm->get('translator'),
                        $sm->get('decision_mapper_meeting')
                    );
                },
                'decision_form_authorization' => function (ServiceLocatorInterface $sm) {
                    return new Authorization(
                        $sm->get('translator')
                    );
                },
                'decision_form_organ_information' => function (ServiceLocatorInterface $sm) {
                    $form = new OrganInformation(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('decision_hydrator'));

                    return $form;
                },
                'decision_form_reorder_document' => function (ServiceLocatorInterface $sm) {
                    $translator = $sm->get('translator');

                    return (new ReorderDocument())
                        ->setTranslator($translator)
                        ->setupElements();
                },
                'decision_hydrator' => function (ServiceLocatorInterface $sm) {
                    return new DoctrineObject(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_fileReader' => function (ServiceLocatorInterface $sm) {
                    //NB: The returned object should implement the FileReader Interface.
                    $config = $sm->get('config');
                    $validFile = $this->getServiceConfig()['filebrowser_valid_file'];

                    return new LocalFileReader(
                        $config['filebrowser_folder'],
                        $validFile
                    );
                },
                'decision_acl' => function (ServiceLocatorInterface $sm) {
                    $acl = $sm->get('acl');

                    // add resources for this module
                    $acl->addResource('organ');
                    $acl->addResource('member');
                    $acl->addResource('dreamspark');
                    $acl->addResource('decision');
                    $acl->addResource('meeting');
                    $acl->addResource('authorization');
                    $acl->addResource('files');
                    $acl->addResource('regulations');

                    // users are allowed to view the organs
                    $acl->allow('guest', 'organ', 'list');
                    $acl->allow('user', 'organ', 'view');

                    // Organ members are allowed to edit organ information of their own organs
                    $acl->allow('active_member', 'organ', ['edit', 'viewAdmin']);

                    // guests are allowed to view birthdays on the homepage
                    $acl->allow('guest', 'member', 'birthdays_today');

                    // users are allowed to view and search members
                    $acl->allow('user', 'member', ['view', 'view_self', 'search', 'birthdays']);
                    $acl->allow('apiuser', 'member', ['view']);

                    $acl->allow('user', 'decision', ['search', 'view_meeting', 'list_meetings']);

                    $acl->allow('user', 'meeting', ['view', 'view_notes', 'view_documents']);

                    $acl->allow('user', 'dreamspark', ['login', 'students']);

                    $acl->allow('user', 'authorization', ['create', 'view_own']);

                    // users are allowed to use the filebrowser
                    $acl->allow('user', 'files', 'browse');

                    // users are allowed to download the regulations
                    $acl->allow('user', 'regulations', ['list', 'download']);

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'decision_doctrine_em' => function (ServiceLocatorInterface $sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
            ],
            /*
             * Regex pattern matching filenames viewable in the browser
             */
            'filebrowser_valid_file' => '[^?*:;{}\\\]*',
        ];
    }
}
