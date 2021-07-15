<?php

namespace Company;

use Company\Form\EditCategory;
use Company\Form\EditCompany;
use Company\Form\EditJob;
use Company\Form\EditLabel;
use Company\Form\EditPackage;
use Company\Mapper\BannerPackage;
use Company\Mapper\Category;
use Company\Mapper\FeaturedPackage;
use Company\Mapper\Job;
use Company\Mapper\Label;
use Company\Mapper\LabelAssignment;
use Company\Mapper\Package;
use Company\Service\Company;
use Company\Service\CompanyQuery;
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

    private function getFormFactories()
    {
        return [
            'company_admin_edit_package_form' => function (ContainerInterface $container) {
                return new EditPackage(
                    $container->get('translator'),
                    'job'
                );
            },
            'company_admin_edit_featuredpackage_form' => function (ContainerInterface $container) {
                return new EditPackage(
                    $container->get('translator'),
                    'featured'
                );
            },
            'company_admin_edit_category_form' => function (ContainerInterface $container) {
                return new EditCategory(
                    $container->get('company_mapper_category'),
                    $container->get('translator'),
                    $container->get('application_get_languages'),
                    $container->get('company_hydrator')
                );
            },
            'company_admin_edit_label_form' => function (ContainerInterface $container) {
                return new EditLabel(
                    $container->get('company_mapper_label'),
                    $container->get('translator'),
                    $container->get('application_get_languages'),
                    $container->get('company_hydrator')
                );
            },
            'company_admin_edit_bannerpackage_form' => function (ContainerInterface $container) {
                return new EditPackage(
                    $container->get('translator'),
                    'banner'
                );
            },
            'company_admin_edit_company_form' => function (ContainerInterface $container) {
                return new EditCompany(
                    $container->get('company_mapper_company'),
                    $container->get('translator')
                );
            },
            'company_admin_edit_job_form' => function (ContainerInterface $container) {
                $form = new EditJob(
                    $container->get('company_mapper_job'),
                    $container->get('translator'),
                    $container->get('application_get_languages'),
                    $container->get('company_hydrator'),
                    $container->get('company_service_companyquery')->getLabelList(false)
                );
                $form->setHydrator($container->get('company_hydrator'));

                return $form;
            },
        ];
    }

    private function getMapperFactories()
    {
        return [
            'company_mapper_company' => function (ContainerInterface $container) {
                return new Mapper\Company(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_job' => function (ContainerInterface $container) {
                return new Job(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_package' => function (ContainerInterface $container) {
                return new Package(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_featuredpackage' => function (ContainerInterface $container) {
                return new FeaturedPackage(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_category' => function (ContainerInterface $container) {
                return new Category(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_label' => function (ContainerInterface $container) {
                return new Label(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_label_assignment' => function (ContainerInterface $container) {
                return new LabelAssignment(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_mapper_bannerpackage' => function (ContainerInterface $container) {
                return new BannerPackage(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
        ];
    }

    private function getOtherFactories()
    {
        return [
            'company_language' => function (ContainerInterface $container) {
                return $container->get('translator');
            },
            'company_hydrator' => function (ContainerInterface $container) {
                return new DoctrineObject(
                    $container->get('doctrine.entitymanager.orm_default')
                );
            },
            'company_service_acl' => AclServiceFactory::class,
        ];
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        $serviceFactories = [
            'company_service_company' => function (ContainerInterface $container) {
                $translator = $container->get('translator');
                $storageService = $container->get('application_service_storage');
                $companyMapper = $container->get('company_mapper_company');
                $packageMapper = $container->get('company_mapper_package');
                $bannerPackageMapper = $container->get('company_mapper_bannerpackage');
                $featuredPackageMapper = $container->get('company_mapper_featuredpackage');
                $jobMapper = $container->get('company_mapper_job');
                $categoryMapper = $container->get('company_mapper_category');
                $labelMapper = $container->get('company_mapper_label');
                $labelAssignmentMapper = $container->get('company_mapper_label_assignment');
                $editCompanyForm = $container->get('company_admin_edit_company_form');
                $editPackageForm = $container->get('company_admin_edit_package_form');
                $editBannerPackageForm = $container->get('company_admin_edit_bannerpackage_form');
                $editFeaturedPackageForm = $container->get('company_admin_edit_featuredpackage_form');
                $editJobForm = $container->get('company_admin_edit_job_form');
                $editCategoryForm = $container->get('company_admin_edit_category_form');
                $editLabelForm = $container->get('company_admin_edit_label_form');
                $languages = $container->get('application_get_languages');
                $aclService = $container->get('company_service_acl');

                return new Company(
                    $translator,
                    $storageService,
                    $companyMapper,
                    $packageMapper,
                    $bannerPackageMapper,
                    $featuredPackageMapper,
                    $jobMapper,
                    $categoryMapper,
                    $labelMapper,
                    $labelAssignmentMapper,
                    $editCompanyForm,
                    $editPackageForm,
                    $editBannerPackageForm,
                    $editFeaturedPackageForm,
                    $editJobForm,
                    $editCategoryForm,
                    $editLabelForm,
                    $languages,
                    $aclService
                );
            },
            'company_service_companyquery' => function (ContainerInterface $container) {
                $translator = $container->get('translator');
                $jobMapper = $container->get('company_mapper_job');
                $categoryMapper = $container->get('company_mapper_category');
                $labelMapper = $container->get('company_mapper_label');
                $aclService = $container->get('company_service_acl');

                return new CompanyQuery(
                    $translator,
                    $jobMapper,
                    $categoryMapper,
                    $labelMapper,
                    $aclService
                );
            },
        ];
        $factories = array_merge(
            $serviceFactories,
            $this->getMapperFactories(),
            $this->getOtherFactories(),
            $this->getFormFactories()
        );

        return [
            'factories' => $factories,
        ];
    }
}
