<?php

declare(strict_types=1);

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;

/**
 * Mappers for package.
 *
 * @template-extends Package<CompanyBannerPackageModel>
 */
class BannerPackage extends Package
{
    /**
     * Returns a random banner from the active banners.
     *
     * @return CompanyBannerPackageModel|null
     */
    public function getBannerPackage(): ?CompanyBannerPackageModel
    {
        $banners = $this->findVisiblePackages();

        if (!empty($banners)) {
            return $banners[array_rand($banners)];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyBannerPackageModel::class;
    }
}
