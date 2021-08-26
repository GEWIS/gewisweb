<?php

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage;
use Company\Model\CompanyJobPackage;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class BannerPackage extends Package
{
    /**
     * Returns an random banner from the active banners.
     */
    public function getBannerPackage()
    {
        $banners = $this->findVisiblePackages();

        return empty($banners) ? null : $banners[array_rand($banners)];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyBannerPackage::class;
    }
}
