<?php

declare(strict_types=1);

namespace Company;

use Company\Form\Company as CompanyForm;
use Company\Form\Job as JobForm;
use Company\Form\JobCategory as JobCategoryForm;
use Company\Form\JobLabel as JobLabelForm;
use Company\Form\JobsTransfer as JobsTransferForm;
use Company\Form\Package as PackageForm;
use Company\Mapper\BannerPackage as BannerPackageMapper;
use Company\Mapper\Category as CategoryMapper;
use Company\Mapper\Company as CompanyMapper;
use Company\Mapper\FeaturedPackage as FeaturedPackageMapper;
use Company\Mapper\Job as JobMapper;
use Company\Mapper\JobUpdate as JobUpdateMapper;
use Company\Mapper\Label as LabelMapper;
use Company\Mapper\Package as PackageMapper;
use Company\Service\Company as CompanySerivce;
use Company\Service\CompanyQuery as CompanyQueryService;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Psr\Container\ContainerInterface;
use User\Authorization\AclServiceFactory;

use function array_merge;

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
            'company_admin_package_form' => static function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'job',
                );
            },
            'company_admin_featuredpackage_form' => static function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'featured',
                );
            },
            'company_admin_jobcategory_form' => static function (ContainerInterface $container) {
                return new JobCategoryForm(
                    $container->get('company_mapper_jobcategory'),
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_joblabel_form' => static function (ContainerInterface $container) {
                return new JobLabelForm(
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_bannerpackage_form' => static function (ContainerInterface $container) {
                return new PackageForm(
                    $container->get(MvcTranslator::class),
                    'banner',
                );
            },
            'company_admin_company_form' => static function (ContainerInterface $container) {
                return new CompanyForm(
                    $container->get('company_mapper_company'),
                    $container->get(MvcTranslator::class),
                );
            },
            'company_admin_job_form' => static function (ContainerInterface $container) {
                return new JobForm(
                    $container->get('company_mapper_job'),
                    $container->get(MvcTranslator::class),
                    $container->get('company_mapper_jobcategory')->findAll(),
                    $container->get('company_mapper_joblabel')->findAll(),
                );
            },
            'company_admin_jobsTransfer_form' => static function (ContainerInterface $container) {
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
            'company_mapper_company' => static function (ContainerInterface $container) {
                return new CompanyMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_job' => static function (ContainerInterface $container) {
                return new JobMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_job_update' => static function (ContainerInterface $container) {
                return new JobUpdateMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_package' => static function (ContainerInterface $container) {
                return new PackageMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_featuredpackage' => static function (ContainerInterface $container) {
                return new FeaturedPackageMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_jobcategory' => static function (ContainerInterface $container) {
                return new CategoryMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_joblabel' => static function (ContainerInterface $container) {
                return new LabelMapper(
                    $container->get('doctrine.entitymanager.orm_default'),
                );
            },
            'company_mapper_bannerpackage' => static function (ContainerInterface $container) {
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
            'company_language' => static function (ContainerInterface $container) {
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
            'company_service_company' => static function (ContainerInterface $container) {
                $aclService = $container->get('company_service_acl');
                $translator = $container->get(MvcTranslator::class);
                $storageService = $container->get('application_service_storage');
                $companyMapper = $container->get('company_mapper_company');
                $packageMapper = $container->get('company_mapper_package');
                $bannerPackageMapper = $container->get('company_mapper_bannerpackage');
                $featuredPackageMapper = $container->get('company_mapper_featuredpackage');
                $jobMapper = $container->get('company_mapper_job');
                $jobUpdateMapper = $container->get('company_mapper_job_update');
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
                $storageConfig = $container->get('config')['storage'];

                return new CompanySerivce(
                    $aclService,
                    $translator,
                    $storageService,
                    $companyMapper,
                    $packageMapper,
                    $bannerPackageMapper,
                    $featuredPackageMapper,
                    $jobMapper,
                    $jobUpdateMapper,
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
                    $storageConfig,
                );
            },
            'company_service_companyquery' => static function (ContainerInterface $container) {
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
            $this->getFormFactories(),
        );

        return [
            'factories' => $factories,
        ];
    }
}
