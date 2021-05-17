<?php

namespace Decision;

use Zend\ServiceManager\ServiceManager;

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
                'decision_service_organ' => 'Decision\Service\Organ',
                'decision_service_decision' => 'Decision\Service\Decision',
                'decision_service_member' => 'Decision\Service\Member',
                'decision_service_companyaccount' => 'Decision\Service\CompanyAccount'
            ],
            'factories' => [
                'decision_mapper_member' => function ($sm) {
                    return new \Decision\Mapper\Member(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_organ' => function ($sm) {
                    return new \Decision\Mapper\Organ(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_meeting' => function ($sm) {
                    return new \Decision\Mapper\Meeting(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_decision' => function ($sm) {
                    return new \Decision\Mapper\Decision(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_authorization' => function ($sm) {
                    return new \Decision\Mapper\Authorization(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_form_searchdecision' => function ($sm) {
                    return new \Decision\Form\SearchDecision(
                        $sm->get('translator')
                    );
                },
                'decision_form_document' => function ($sm) {
                    return new \Decision\Form\Document(
                        $sm->get('translator'),
                        $sm->get('decision_mapper_meeting')
                    );
                },
                'decision_form_notes' => function ($sm) {
                    return new \Decision\Form\Notes(
                        $sm->get('translator'),
                        $sm->get('decision_mapper_meeting')
                    );
                },
                'decision_form_authorization' => function ($sm) {
                    return new \Decision\Form\Authorization(
                        $sm->get('translator')
                    );
                },
                'decision_form_organ_information' => function ($sm) {
                    $form = new \Decision\Form\OrganInformation(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('decision_hydrator'));
                    return $form;
                },
                'decision_form_reorder_document' => function (ServiceManager $sm) {
                    $translator = $sm->get('translator');

                    return (new \Decision\Form\ReorderDocument())
                        ->setTranslator($translator)
                        ->setupElements();
                },
                'decision_hydrator' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_fileReader' => function ($sm) {
                    //NB: The returned object should implement the FileReader Interface.
                    $config = $sm->get('config');
                    $validFile = $this->getServiceConfig()['filebrowser_valid_file'];
                    return new \Decision\Controller\FileBrowser\LocalFileReader(
                        $config['filebrowser_folder'],
                        $validFile
                    );
                },
                'decision_acl' => function ($sm) {
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
                    $acl->addResource('companyaccount');

                    // users are allowed to view the organs
                    $acl->allow('guest', 'organ', 'list');
                    $acl->allow('user', 'organ', 'view');

                    // Organ members are allowed to edit organ information of their own organs
                    $acl->allow('active_member', 'organ', 'edit');

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

                    // companies are allowed to view the company panel
                    $acl->allow('company_user', 'companyaccount', 'view');

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'decision_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ],
            /*
             * Regex pattern matching filenames viewable in the browser
             */
            'filebrowser_valid_file' => '[^?*:;{}\\\]*'
        ];
    }
}
