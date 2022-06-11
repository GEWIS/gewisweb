<?php

namespace Decision;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Decision\Controller\FileBrowser\LocalFileReader;
use Decision\Form\{
    Authorization as AuthorizationForm,
    Document as DocumentForm,
    Minutes as MinutesForm,
    OrganInformation as OrganInformationForm,
    ReorderDocument as ReorderDocumentForm,
    SearchDecision as SearchDecisionForm,
};
use Decision\Mapper\{
    Authorization as AuthorizationMapper,
    Decision as DecisionMapper,
    Meeting as MeetingMapper,
    Member as MemberMapper,
    Organ as OrganMapper,
};
use Decision\Service\{
    Decision as DecisionService,
    Member as MemberService,
    MemberInfo as MemberInfoService,
    Organ as OrganService,
};
use Doctrine\Laminas\Hydrator\DoctrineObject;
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
                'decision_service_organ' => function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $entityManager = $container->get('doctrine.entitymanager.orm_default');
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $organMapper = $container->get('decision_mapper_organ');
                    $organInformationForm = $container->get('decision_form_organ_information');
                    $organInformationConfig = $container->get('config')['organ_information'];

                    return new OrganService(
                        $aclService,
                        $translator,
                        $entityManager,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $organMapper,
                        $organInformationForm,
                        $organInformationConfig,
                    );
                },
                'decision_service_decision' => function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $meetingMapper = $container->get('decision_mapper_meeting');
                    $decisionMapper = $container->get('decision_mapper_decision');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $minutesForm = $container->get('decision_form_minutes');
                    $documentForm = $container->get('decision_form_document');
                    $reorderDocumentForm = $container->get('decision_form_reorder_document');
                    $searchDecisionForm = $container->get('decision_form_searchdecision');
                    $authorizationForm = $container->get('decision_form_authorization');

                    return new DecisionService(
                        $aclService,
                        $translator,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $meetingMapper,
                        $decisionMapper,
                        $authorizationMapper,
                        $minutesForm,
                        $documentForm,
                        $reorderDocumentForm,
                        $searchDecisionForm,
                        $authorizationForm,
                    );
                },
                'decision_service_member' => function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $memberMapper = $container->get('decision_mapper_member');
                    $authorizationMapper = $container->get('decision_mapper_authorization');

                    return new MemberService(
                        $aclService,
                        $translator,
                        $memberMapper,
                        $authorizationMapper,
                    );
                },
                'decision_service_memberinfo' => function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $photoService = $container->get('photo_service_photo');
                    $memberMapper = $container->get('decision_mapper_member');
                    $apiAppAuthenticationMapper = $container->get('user_mapper_apiappauthentication');
                    $photoConfig = $container->get('config')['photo'];

                    return new MemberInfoService(
                        $aclService,
                        $translator,
                        $photoService,
                        $memberMapper,
                        $apiAppAuthenticationMapper,
                        $photoConfig,
                    );
                },
                'decision_mapper_member' => function (ContainerInterface $container) {
                    return new MemberMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_organ' => function (ContainerInterface $container) {
                    return new OrganMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_meeting' => function (ContainerInterface $container) {
                    return new MeetingMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_decision' => function (ContainerInterface $container) {
                    return new DecisionMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_authorization' => function (ContainerInterface $container) {
                    return new AuthorizationMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_form_searchdecision' => function (ContainerInterface $container) {
                    return new SearchDecisionForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_document' => function (ContainerInterface $container) {
                    return new DocumentForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_minutes' => function (ContainerInterface $container) {
                    return new MinutesForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_authorization' => function (ContainerInterface $container) {
                    return new AuthorizationForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_organ_information' => function (ContainerInterface $container) {
                    $form = new OrganInformationForm(
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('decision_hydrator'));

                    return $form;
                },
                'decision_form_reorder_document' => function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);

                    return (new ReorderDocumentForm())
                        ->setTranslator($translator)
                        ->setupElements();
                },
                'decision_hydrator' => function (ContainerInterface $container) {
                    return new DoctrineObject(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_fileReader' => function (ContainerInterface $container) {
                    //NB: The returned object should implement the FileReader Interface.
                    $config = $container->get('config');
                    $validFile = $this->getServiceConfig()['filebrowser_valid_file'];

                    return new LocalFileReader(
                        $config['filebrowser_folder'],
                        $validFile,
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
