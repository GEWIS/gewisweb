<?php

namespace Company;

use Company\Form\EditCategory;
use Company\Form\EditCompany;
use Company\Form\EditJob;
use Company\Form\EditPackage;
use Company\Mapper\BannerPackage;
use Company\Mapper\Category;
use Company\Mapper\Company;
use Company\Mapper\FeaturedPackage;
use Company\Mapper\Job;
use Company\Mapper\Package;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;

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
        return include __DIR__.'/config/module.config.php';
    }

    private function getFormFactories()
    {
        return [
            'company_admin_edit_package_form' => function ($sm) {
                return new EditPackage(
                    $sm->get('translator'),
                    "job"
                );
            },
            'company_admin_edit_featuredpackage_form' => function ($sm) {
                return new EditPackage(
                    $sm->get('translator'),
                    "featured"
                );
            },
            'company_admin_edit_category_form' => function ($sm) {
                $form = new EditCategory(
                    $sm->get('company_mapper_category'),
                    $sm->get('translator'),
                    $sm->get('application_get_languages'),
                    $sm->get('company_hydrator')
                );
                return $form;
            },
            'company_admin_edit_bannerpackage_form' => function ($sm) {
                return new EditPackage(
                    $sm->get('translator'),
                    "banner"
                );
            },
            'company_admin_edit_company_form' => function ($sm) {
                return new EditCompany(
                    $sm->get('company_mapper_company'),
                    $sm->get('translator')
                );
            },
            'company_admin_edit_job_form' => function ($sm) {
                $form = new EditJob(
                    $sm->get('company_mapper_job'),
                    $sm->get('translator'),
                    $sm->get('application_get_languages'),
                    $sm->get('company_hydrator')
                );
                $form->setHydrator($sm->get('company_hydrator'));
                return $form;
            },
        ];
    }
    private function getMapperFactories()
    {
        return [
            'company_mapper_company' => function ($sm) {
                return new Company(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_mapper_job' => function ($sm) {
                return new Job(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_mapper_package' => function ($sm) {
                return new Package(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_mapper_featuredpackage' => function ($sm) {
                return new FeaturedPackage(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_mapper_category' => function ($sm) {
                return new Category(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_mapper_bannerpackage' => function ($sm) {
                return new BannerPackage(
                    $sm->get('company_doctrine_em')
                );
            },
        ];
    }

    private function getOtherFactories()
    {
        return [
            'company_doctrine_em' => function ($sm) {
                return $sm->get('doctrine.entitymanager.orm_default');
            },
            'company_language' => function ($sm) {
                return $sm->get('translator');
            },
            'company_hydrator' => function ($sm) {
                return new DoctrineObject(
                    $sm->get('company_doctrine_em')
                );
            },
            'company_acl' => function ($sm) {
                $acl = $sm->get('acl');

                // add resource
                $acl->addResource('company');

                $acl->allow('guest', 'company', 'viewFeaturedCompany');
                $acl->allow('guest', 'company', 'list');
                $acl->allow('guest', 'company', 'view');
                $acl->allow('guest', 'company', 'listVisibleCategories');
                $acl->allow('guest', 'company', 'showBanner');
                $acl->allow('company_admin', 'company', ['insert', 'edit', 'delete', 'listall', 'listAllCategories']);

                return $acl;
            },
        ];
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        $factories = array_merge($this->getMapperFactories(), $this->getOtherFactories(), $this->getFormFactories());
        return [
            'invokables' => [
                'company_service_company' => 'Company\Service\Company',
            ],
            'factories' => $factories,
        ];
    }
}
