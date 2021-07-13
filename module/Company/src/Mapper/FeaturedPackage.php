<?php

namespace Company\Mapper;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifycations will be
 * overwritten.
 */
class FeaturedPackage extends Package
{
    /**
     * Returns an random featured package from the active featured packages,
     * and null when there is no featured package in the current language.
     */
    public function getFeaturedPackage($locale)
    {
        $featuredPackages = $this->findVisiblePackagesByLocale($locale);
        if (!empty($featuredPackages)) {
            return $featuredPackages[array_rand($featuredPackages->toArray())];
        }

        return null;
    }

    /**
     * Find all packages that should be visible, and returns an editable version of them.
     *
     * @return Collection
     */
    public function findVisiblePackagesByLocale($locale)
    {
        $qb = $this->getVisiblePackagesQueryBuilder();
        $qb->andWhere('p.language>=?1')
            ->setParameter(1, $locale);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Company\Model\CompanyFeaturedPackage');
    }
}
