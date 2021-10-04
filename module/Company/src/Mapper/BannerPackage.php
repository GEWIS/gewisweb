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
     */
    public function getBannerPackage()
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
