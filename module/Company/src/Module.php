<?php

namespace Company;

use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Company\Form\{
    JobCategory as JobCategoryForm,
    Company as CompanyForm,
    Job as JobForm,
    JobLabel as JobLabelForm,
    JobsTransfer as JobsTransferForm,
    Package as PackageForm,
};
use Company\Mapper\{
    BannerPackage as BannerPackageMapper,
    Category as CategoryMapper,
    Company as CompanyMapper,
    FeaturedPackage as FeaturedPackageMapper,
    Job as JobMapper,
    Label as LabelMapper,
    Package as PackageMapper,
};
use Company\Service\{
    Company as CompanySerivce,
    CompanyQuery as CompanyQueryService,
};
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
     * @return array
     */
    private function getFormFactories(): array
    {
        return [
            'company_admin_package_form' => function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'job',
                );
            },
            'company_admin_featuredpackage_form' => function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'featured',
                );
            },
            'company_admin_jobcategory_form' => function (ContainerInterface $container) {
                return new JobCategoryForm(
                    $container->get('company_mapper_jobcategory'),
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_joblabel_form' => function (ContainerInterface $container) {
                return new JobLabelForm(
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_bannerpackage_form' => function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'banner',
                );
            },
            'company_admin_company_form' => function (ContainerInterface $container) {
                return new CompanyForm(
                    $container->get('company_mapper_company'),
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_job_form' => function (ContainerInterface $container) {
                return new JobForm(
                    $container->get('company_mapper_job'),
                    $container->get(MvcTranslator::class),
                    $container->get('company_mapper_jobcategory')->findAll(),
                    $container->get('company_mapper_joblabel')->findAll(),
                );
            },
            'company_admin_jobsTransfer_form' => function (ContainerInterface $container) {
                return new JobsTransferForm($container->get(MvcTranslator::class));
            },
        ];
    }

    /**
     * @return array
     */
    private function getMapperFactories(): array
    {
        return [
            'company_mapper_company' => function (ContainerInterface $container) {
                return new CompanyMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_job' => function (ContainerInterface $container) {
                return new JobMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_package' => function (ContainerInterface $container) {
                return new PackageMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_featuredpackage' => function (ContainerInterface $container) {
                return new FeaturedPackageMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_jobcategory' => function (ContainerInterface $container) {
                return new CategoryMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_joblabel' => function (ContainerInterface $container) {
                return new LabelMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_bannerpackage' => function (ContainerInterface $container) {
                return new BannerPackageMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
        ];
    }

    /**
     * @return array
     */
    private function getOtherFactories(): array
    {
        return [
            'company_language' => function (ContainerInterface $container) {
                return $container->get(MvcTranslator::class);
            },
            'company_service_acl' => AclServiceFactory::class,
        ];
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig(): array
    {
        $serviceFactories = [
            'company_service_company' => function (ContainerInterface $container) {
                $aclService = $container->get('company_service_acl');
                $translator = $container->get(MvcTranslator::class);
                $storageService = $container->get('application_service_storage');
                $companyMapper = $container->get('company_mapper_company');
                $packageMapper = $container->get('company_mapper_package');
                $bannerPackageMapper = $container->get('company_mapper_bannerpackage');
                $featuredPackageMapper = $container->get('company_mapper_featuredpackage');
                $jobMapper = $container->get('company_mapper_job');
                $categoryMapper = $container->get('company_mapper_jobcategory');
                $labelMapper = $container->get('company_mapper_joblabel');
                $companyForm = $container->get('company_admin_company_form');
                $packageForm = $container->get('company_admin_package_form');
                $bannerPackageForm = $container->get('company_admin_bannerpackage_form');
                $featuredPackageForm = $container->get('company_admin_featuredpackage_form');
                $jobForm = $container->get('company_admin_job_form');
                $jobCategoryForm = $container->get('company_admin_jobcategory_form');
                $jobLabelForm = $container->get('company_admin_joblabel_form');
                $userService = $container->get('user_service_user');

                return new CompanySerivce(
                    $aclService,
                    $translator,
                    $storageService,
                    $companyMapper,
                    $packageMapper,
                    $bannerPackageMapper,
                    $featuredPackageMapper,
                    $jobMapper,
                    $categoryMapper,
                    $labelMapper,
                    $companyForm,
                    $packageForm,
                    $bannerPackageForm,
                    $featuredPackageForm,
                    $jobForm,
                    $jobCategoryForm,
                    $jobLabelForm,
                    $userService,
                );
            },
            'company_service_companyquery' => function (ContainerInterface $container) {
                $aclService = $container->get('company_service_acl');
                $translator = $container->get(MvcTranslator::class);
                $jobMapper = $container->get('company_mapper_job');
                $categoryMapper = $container->get('company_mapper_jobcategory');
                $labelMapper = $container->get('company_mapper_joblabel');

                return new CompanyQueryService(
                    $aclService,
                    $translator,
                    $jobMapper,
                    $categoryMapper,
                    $labelMapper,
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
