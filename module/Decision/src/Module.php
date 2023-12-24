<?php

declare(strict_types=1);

namespace Decision;

use Decision\Controller\FileBrowser\LocalFileReader;
use Decision\Form\Authorization as AuthorizationForm;
use Decision\Form\AuthorizationRevocation as AuthorizationRevocationForm;
use Decision\Form\Document as DocumentForm;
use Decision\Form\Minutes as MinutesForm;
use Decision\Form\OrganInformation as OrganInformationForm;
use Decision\Form\ReorderDocument as ReorderDocumentForm;
use Decision\Form\SearchDecision as SearchDecisionForm;
use Decision\Mapper\Authorization as AuthorizationMapper;
use Decision\Mapper\Decision as DecisionMapper;
use Decision\Mapper\Meeting as MeetingMapper;
use Decision\Mapper\MeetingDocument as MeetingDocumentMapper;
use Decision\Mapper\MeetingMinutes as MeetingMinutesMapper;
use Decision\Mapper\Member as MemberMapper;
use Decision\Mapper\Organ as OrganMapper;
use Decision\Mapper\SubDecision as SubDecisionMapper;
use Decision\Service\Decision as DecisionService;
use Decision\Service\Gdpr as GdprService;
use Decision\Service\Member as MemberService;
use Decision\Service\MemberInfo as MemberInfoService;
use Decision\Service\Organ as OrganService;
use Doctrine\Laminas\Hydrator\DoctrineObject;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
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
                'decision_service_organ' => static function (ContainerInterface $container) {
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
                'decision_service_decision' => static function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $storageService = $container->get('application_service_storage');
                    $emailService = $container->get('application_service_email');
                    $memberMapper = $container->get('decision_mapper_member');
                    $meetingMapper = $container->get('decision_mapper_meeting');
                    $meetingDocumentMapper = $container->get('decision_mapper_meeting_document');
                    $meetingMinutesMapper = $container->get('decision_mapper_meeting_minutes');
                    $decisionMapper = $container->get('decision_mapper_decision');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $minutesForm = $container->get('decision_form_minutes');
                    $documentForm = $container->get('decision_form_document');
                    $reorderDocumentForm = $container->get('decision_form_reorder_document');
                    $searchDecisionForm = $container->get('decision_form_searchdecision');
                    $authorizationForm = $container->get('decision_form_authorization');
                    $authorizationRevocationForm = $container->get('decision_form_authorization_revocation');

                    return new DecisionService(
                        $aclService,
                        $translator,
                        $storageService,
                        $emailService,
                        $memberMapper,
                        $meetingMapper,
                        $meetingDocumentMapper,
                        $meetingMinutesMapper,
                        $decisionMapper,
                        $authorizationMapper,
                        $minutesForm,
                        $documentForm,
                        $reorderDocumentForm,
                        $searchDecisionForm,
                        $authorizationForm,
                        $authorizationRevocationForm,
                    );
                },
                'decision_service_gdpr' => static function (ContainerInterface $container) {
                    $aclService = $container->get('decision_service_acl');
                    $translator = $container->get(MvcTranslator::class);
                    $activityMapper = $container->get('activity_mapper_activity');
                    $apiAppAuthenticationMapper = $container->get('user_mapper_apiappauthentication');
                    $authorizationMapper = $container->get('decision_mapper_authorization');
                    $companyMapper = $container->get('company_mapper_company');
                    $courseDocumentMapper = $container->get('education_mapper_courseDocument');
                    $jobMapper = $container->get('company_mapper_job');
                    $loginAttemptMapper = $container->get('user_mapper_loginAttempt');
                    $memberMapper = $container->get('decision_mapper_member');
                    $pollMapper = $container->get('frontpage_mapper_poll');
                    $pollCommentMapper = $container->get('frontpage_mapper_poll_comment');
                    $photoMapper = $container->get('photo_mapper_photo');
                    $profilePhotoMapper = $container->get('photo_mapper_profile_photo');
                    $signupMapper = $container->get('activity_mapper_signup');
                    $subDecisionMapper = $container->get('decision_mapper_subDecision');
                    $tagMapper = $container->get('photo_mapper_tag');
                    $userMapper = $container->get('user_mapper_user');
                    $voteMapper = $container->get('photo_mapper_vote');

                    return new GdprService(
                        $aclService,
                        $translator,
                        $activityMapper,
                        $apiAppAuthenticationMapper,
                        $authorizationMapper,
                        $companyMapper,
                        $courseDocumentMapper,
                        $jobMapper,
                        $loginAttemptMapper,
                        $memberMapper,
                        $pollMapper,
                        $pollCommentMapper,
                        $photoMapper,
                        $profilePhotoMapper,
                        $signupMapper,
                        $subDecisionMapper,
                        $tagMapper,
                        $userMapper,
                        $voteMapper,
                    );
                },
                'decision_service_member' => static function (ContainerInterface $container) {
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
                'decision_service_memberinfo' => static function (ContainerInterface $container) {
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
                'decision_mapper_member' => static function (ContainerInterface $container) {
                    return new MemberMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_organ' => static function (ContainerInterface $container) {
                    return new OrganMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_meeting' => static function (ContainerInterface $container) {
                    return new MeetingMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_meeting_document' => static function (ContainerInterface $container) {
                    return new MeetingDocumentMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_meeting_minutes' => static function (ContainerInterface $container) {
                    return new MeetingMinutesMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_decision' => static function (ContainerInterface $container) {
                    return new DecisionMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_subDecision' => static function (ContainerInterface $container) {
                    return new SubDecisionMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_mapper_authorization' => static function (ContainerInterface $container) {
                    return new AuthorizationMapper(
                        $container->get('doctrine.entitymanager.orm_default'),
                    );
                },
                'decision_form_searchdecision' => static function (ContainerInterface $container) {
                    return new SearchDecisionForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_document' => static function (ContainerInterface $container) {
                    return new DocumentForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_minutes' => static function (ContainerInterface $container) {
                    return new MinutesForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_authorization' => static function (ContainerInterface $container) {
                    return new AuthorizationForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_authorization_revocation' => static function (ContainerInterface $container) {
                    return new AuthorizationRevocationForm(
                        $container->get(MvcTranslator::class),
                    );
                },
                'decision_form_organ_information' => static function (ContainerInterface $container) {
                    $form = new OrganInformationForm(
                        $container->get(MvcTranslator::class),
                    );
                    $form->setHydrator($container->get('decision_hydrator'));

                    return $form;
                },
                'decision_form_reorder_document' => static function (ContainerInterface $container) {
                    $translator = $container->get(MvcTranslator::class);

                    return (new ReorderDocumentForm())
                        ->setTranslator($translator)
                        ->setupElements();
                },
                'decision_hydrator' => static function (ContainerInterface $container) {
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
