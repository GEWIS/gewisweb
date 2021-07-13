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
use Interop\Container\ContainerInterface;

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
     * @noinspection PhpParamsInspection
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'decision_service_organ' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('decision_acl');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $userService = $container->get('user_service_user');
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $organMapper = $container->get('decision_mapper_organ');
                    $organInformationForm = $container->get('decision_form_organ_information');
                    $organInformationConfig = $container->get('config')['organ_information'];

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
                'decision_service_decision' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('decision_acl');
                    $userService = $container->get('user_service_user');
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $meetingMapper = $container->get('decision_mapper_meeting');
                    $decisionMapper = $container->get('decision_mapper_decision');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $notesForm = $container->get('decision_form_notes');
                    $documentForm = $container->get('decision_form_document');
                    $reorderDocumentForm = $container->get('decision_form_reorder_document');
                    $searchDecisionForm = $container->get('decision_form_searchdecision');
                    $authorizationForm = $container->get('decision_form_authorization');

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
                'decision_service_member' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('decision_acl');
                    $userService = $container->get('user_service_user');
                    $memberMapper = $container->get('decision_mapper_member');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $config = $container->get('config');

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
                'decision_service_memberinfo' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $userRole = $container->get('user_role');
                    $acl = $container->get('decision_acl');
                    $photoService = $container->get('photo_service_photo');
                    $memberMapper = $container->get('decision_mapper_member');

                    return new Service\MemberInfo(
                        $translator,
                        $userRole,
                        $acl,
                        $photoService,
                        $memberMapper
                    );
                },
                'decision_mapper_member' => function (ContainerInterface $container) {
                    return new Member(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_mapper_organ' => function (ContainerInterface $container) {
                    return new Organ(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_mapper_meeting' => function (ContainerInterface $container) {
                    return new Meeting(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_mapper_decision' => function (ContainerInterface $container) {
                    return new Decision(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_mapper_authorization' => function (ContainerInterface $container) {
                    return new Mapper\Authorization(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_form_searchdecision' => function (ContainerInterface $container) {
                    return new SearchDecision(
                        $container->get('translator')
                    );
                },
                'decision_form_document' => function (ContainerInterface $container) {
                    return new Document(
                        $container->get('translator')
                    );
                },
                'decision_form_notes' => function (ContainerInterface $container) {
                    return new Notes(
                        $container->get('translator'),
                        $container->get('decision_mapper_meeting')
                    );
                },
                'decision_form_authorization' => function (ContainerInterface $container) {
                    return new Authorization(
                        $container->get('translator')
                    );
                },
                'decision_form_organ_information' => function (ContainerInterface $container) {
                    $form = new OrganInformation(
                        $container->get('translator')
                    );
                    $form->setHydrator($container->get('decision_hydrator'));

                    return $form;
                },
                'decision_form_reorder_document' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');

                    return (new ReorderDocument())
                        ->setTranslator($translator)
                        ->setupElements();
                },
                'decision_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default')
                    );
                },
                'decision_fileReader' => function (ContainerInterface $container) {
                    //NB: The returned object should implement the FileReader Interface.
                    $config = $container->get('config');
                    $validFile = $this->getServiceConfig()['filebrowser_valid_file'];

                    return new LocalFileReader(
                        $config['filebrowser_folder'],
                        $validFile
                    );
                },
                'decision_acl' => function (ContainerInterface $container) {
                    $acl = $container->get('acl');

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
            ],
            /*
             * Regex pattern matching filenames viewable in the browser
             */
            'filebrowser_valid_file' => '[^?*:;{}\\\]*',
        ];
    }
}
