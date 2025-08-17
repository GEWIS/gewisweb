<?php

declare(strict_types=1);

namespace Company\Mapper;

use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;
use Override;

use function array_rand;

/**
 * Mappers for package.
 *
 * @template-extends Package<CompanyBannerPackageModel>
 */
class BannerPackage extends Package
{
    /**
     * Returns a random banner from the active banners.
     */
    public function getBannerPackage(): ?CompanyBannerPackageModel
    {
        $banners = $this->findVisiblePackages();

        if (!empty($banners)) {
            return $banners[array_rand($banners)];
        }

        return null;
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return CompanyBannerPackageModel::class;
    }
}
