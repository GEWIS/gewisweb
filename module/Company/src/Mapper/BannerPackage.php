<?php

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifications will be
 * overwritten.
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
