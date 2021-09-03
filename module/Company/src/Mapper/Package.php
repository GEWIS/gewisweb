<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\{
    CompanyBannerPackage as CompanyBannerPackageModel,
    CompanyFeaturedPackage as CompanyFeaturedPackageModel,
    CompanyJobPackage as CompanyJobPackageModel,
    CompanyPackage as CompanyPackageModel,
};
use DateTime;
use Exception;

/**
 * Mappers for package.
 *
 * NOTE: Packages will be modified externally by a script. Modifications will be
 * overwritten.
 */
class Package extends BaseMapper
{
    /**
     * Finds the package with the given id.
     *
     * @param int $packageId
     */
    public function findPackage($packageId)
    {
        return $this->getRepository()->findOneBy(['id' => $packageId]);
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     */
    public function findFuturePackageExpirationsBeforeDate($date)
    {
        $objectRepository = $this->getRepository();
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            // All packages that will expire between today and then, ordered smallest first
            ->andWhere('p.expires>CURRENT_DATE()')
            ->andWhere('p.expires<=:date')
            ->orderBy('p.expires', 'ASC')
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     */
    public function findFuturePackageStartsBeforeDate($date)
    {
        $objectRepository = $this->getRepository();
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            // All packages that will start between today and then, ordered smallest first
            ->andWhere('p.starts>CURRENT_DATE()')
            ->andWhere('p.starts<=:date')
            ->orderBy('p.starts', 'ASC')
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    protected function getVisiblePackagesQueryBuilder()
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.published=1')
            ->andWhere('p.starts<=CURRENT_DATE()')
            ->andWhere('p.expires>=CURRENT_DATE()');

        return $qb;
    }

    /**
     * Find all packages that should be visible, and returns an editable version of them.
     *
     * @return array
     */
    public function findVisiblePackages()
    {
        $qb = $this->getVisiblePackagesQueryBuilder();

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all packages, and returns an editable version of them.
     *
     * @return CompanyPackageModel|null
     */
    public function findEditablePackage($packageId)
    {
        $objectRepository = $this->getRepository(); // From clause is integrated in this statement
        $qb = $objectRepository->createQueryBuilder('p');
        $qb->select('p')->where('p.id=:packageId')
            ->setParameter('packageId', $packageId)
            ->setMaxResults(1);

        $packages = $qb->getQuery()->getResult();

        if (1 != count($packages)) {
            return null;
        }

        return $packages[0];
    }

    /**
     * @param string $type
     *
     * @return CompanyPackageModel
     *
     * @throws Exception
     */
    public function createPackage(string $type): CompanyPackageModel
    {
        return match ($type) {
            'banner' => new CompanyBannerPackageModel(),
            'featured' => new CompanyFeaturedPackageModel(),
            'job' => new CompanyJobPackageModel(),
            default => throw new Exception('Unknown type for class that extends CompanyPackage'),
        };
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return CompanyJobPackageModel::class;
    }
}
