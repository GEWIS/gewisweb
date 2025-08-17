<?php

declare(strict_types=1);

namespace Company\Mapper;

use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Override;

use function array_rand;

/**
 * Mappers for package.
 *
 * @template-extends Package<CompanyFeaturedPackageModel>
 */
class FeaturedPackage extends Package
{
    /**
     * Returns a random featured package from the active featured packages,
     * and null when there is no featured package.
     */
    public function getFeaturedPackage(): ?CompanyFeaturedPackageModel
    {
        $featuredPackages = $this->findVisiblePackages();

        if (!empty($featuredPackages)) {
            return $featuredPackages[array_rand($featuredPackages)];
        }

        return null;
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return CompanyFeaturedPackageModel::class;
    }
}
