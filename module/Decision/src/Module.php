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
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'decision_service_organ' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $organMapper = $container->get('decision_mapper_organ');
                    $organInformationForm = $container->get('decision_form_organ_information');
                    $organInformationConfig = $container->get('config')['organ_information'];
                    $aclService = $container->get('decision_service_acl');

                    return new Service\Organ(
                        $translator,
                        $entityManager,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $organMapper,
                        $organInformationForm,
                        $organInformationConfig,
                        $aclService
                    );
                },
                'decision_service_decision' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
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
                    $aclService = $container->get('decision_service_acl');

                    return new Service\Decision(
                        $translator,
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
                        $authorizationForm,
                        $aclService
                    );
                },
                'decision_service_member' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $memberMapper = $container->get('decision_mapper_member');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $config = $container->get('config');
                    $aclService = $container->get('decision_service_acl');

                    return new Service\Member(
                        $translator,
                        $memberMapper,
                        $authorizationMapper,
                        $config,
                        $aclService
                    );
                },
                'decision_service_memberinfo' => function (ContainerInterface $container) {
                    $translator = $container->get('translator');
                    $photoService = $container->get('photo_service_photo');
                    $memberMapper = $container->get('decision_mapper_member');
                    $aclService = $container->get('decision_service_acl');

                    return new Service\MemberInfo(
                        $translator,
                        $photoService,
                        $memberMapper,
                        $aclService
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
                'decision_service_acl' => AclServiceFactory::class,
            ],
            /*
             * Regex pattern matching filenames viewable in the browser
             */
            'filebrowser_valid_file' => '[^?*:;{}\\\]*',
        ];
    }
}
