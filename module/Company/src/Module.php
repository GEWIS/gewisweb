<?php

declare(strict_types=1);

namespace Company;

use Application\Form\Factory\BaseFormFactory;
use Application\Mapper\Factory\BaseMapperFactory;
use Company\Form\Company as CompanyForm;
use Company\Form\Factory\CompanyFactory as CompanyFormFactory;
use Company\Form\Factory\JobCategoryFactory as JobCategoryFormFactory;
use Company\Form\Factory\JobFactory as JobFormFactory;
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
use Company\Service\AclService;
use Company\Service\Company as CompanyService;
use Company\Service\CompanyQuery as CompanyQueryService;
use Company\Service\Factory\CompanyFactory as CompanyServiceFactory;
use Company\Service\Factory\CompanyQueryFactory as CompanyQueryServiceFactory;
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
                // Services
                AclService::class => AclServiceFactory::class,
                CompanyService::class => CompanyServiceFactory::class,
                CompanyQueryService::class => CompanyQueryServiceFactory::class,
                // Mappers
                BannerPackageMapper::class => BaseMapperFactory::class,
                CategoryMapper::class => BaseMapperFactory::class,
                CompanyMapper::class => BaseMapperFactory::class,
                FeaturedPackageMapper::class => BaseMapperFactory::class,
                JobMapper::class => BaseMapperFactory::class,
                JobUpdateMapper::class => BaseMapperFactory::class,
                LabelMapper::class => BaseMapperFactory::class,
                PackageMapper::class => BaseMapperFactory::class,
                // Forms
                CompanyForm::class => CompanyFormFactory::class,
                JobCategoryForm::class => JobCategoryFormFactory::class,
                JobForm::class => JobFormFactory::class,
                JobLabelForm::class => BaseFormFactory::class,
                JobsTransferForm::class => BaseFormFactory::class,
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
                'company_admin_bannerpackage_form' => static function (ContainerInterface $container) {
                    return new PackageForm(
                        $container->get(MvcTranslator::class),
                        'banner',
                    );
                },
                // Commands
                // N/A
            ],
        ];
    }
}
