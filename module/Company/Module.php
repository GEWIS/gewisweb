<?php

namespace Company;

class Module
{
    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__.'/src/'.__NAMESPACE__,
                ],
            ],
        ];
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
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'company_service_company' => 'Company\Service\Company',
            ],
            'factories' => [
                'company_mapper_company' => function ($sm) {
                    return new \Company\Mapper\Company(
                        $sm->get('company_doctrine_em')
                    );
                },
                'company_mapper_job' => function ($sm) {
                    return new \Company\Mapper\Job(
                        $sm->get('company_doctrine_em')
                    );
                },
                'company_mapper_package' => function ($sm) {
                    return new \Company\Mapper\Package(
                        $sm->get('company_doctrine_em')
                    );
                },
                'company_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                },
                'company_admin_edit_package_form' => function ($sm) {
                    return new \Company\Form\EditPackage(
                        $sm->get('translator')
                    );
                },
                'company_admin_edit_company_form' => function ($sm) {
                    return new \Company\Form\EditCompany(
                        $sm->get('translator')
                    );
                },
                'company_language' => function ($sm) {
                    return $sm->get('translator');
                },
                'company_admin_edit_job_form' => function ($sm) {
                    return new \Company\Form\EditJob(
                        $sm->get('translator')
                    );
                },
                'company_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    // add resource
                    $acl->addResource('company');

                    // users (logged in GEWIS members) are allowed to view exams
                    // TODO: besides users, also people on the TU/e network
                    // are allowed to view exams
                    $acl->allow('guest', 'company', 'list');
                    $acl->allow('guest', 'company', 'view');
                    $acl->allow('admin', 'company', 'insert');
                    $acl->allow('admin', 'company', 'edit');
                    $acl->allow('admin', 'company', 'delete');
                    $acl->allow('admin', 'company', 'listall'); // Can use admin interface

                    return $acl;
                },
        ]
    ];
    }
}
