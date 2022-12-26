<?php

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\{
    CompanyBannerPackage as CompanyBannerPackageModel,
    CompanyFeaturedPackage as CompanyFeaturedPackageModel,
    CompanyJobPackage as CompanyJobPackageModel,
    CompanyPackage as CompanyPackageModel,
    Enums\CompanyPackageTypes};
use DateTime;
use Doctrine\ORM\QueryBuilder;
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
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     *
     * @return array
     */
    public function findFuturePackageExpirationsBeforeDate(DateTime $date): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.published=1')
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
     *
     * @return array
     */
    public function findFuturePackageStartsBeforeDate(DateTime $date): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.published=1')
            // All packages that will start between today and then, ordered smallest first
            ->andWhere('p.starts>CURRENT_DATE()')
            ->andWhere('p.starts<=:date')
            ->orderBy('p.starts', 'ASC')
            ->setParameter('date', $date);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return QueryBuilder
     */
    protected function getVisiblePackagesQueryBuilder(): QueryBuilder
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.published=1')
            ->andWhere('p.starts<=CURRENT_DATE()')
            ->andWhere('p.expires>=CURRENT_DATE()');

        return $qb;
    }

    /**
     * Find all packages that should be visible, and returns an editable version of them.
     *
     * @return array
     */
    public function findVisiblePackages(): array
    {
        $qb = $this->getVisiblePackagesQueryBuilder();

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all packages, and returns an editable version of them.
     *
     * @param int $packageId
     *
     * @return CompanyPackageModel|null
     */
    public function findEditablePackage(int $packageId): ?CompanyPackageModel
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.id=:packageId')
            ->setParameter('packageId', $packageId)
            ->setMaxResults(1);

        $packages = $qb->getQuery()->getResult();

        if (1 != count($packages)) {
            return null;
        }

        return $packages[0];
    }

    public function createPackage(CompanyPackageTypes $type): CompanyPackageModel
    {
        return match ($type) {
            CompanyPackageTypes::Banner => new CompanyBannerPackageModel(),
            CompanyPackageTypes::Featured => new CompanyFeaturedPackageModel(),
            CompanyPackageTypes::Job => new CompanyJobPackageModel(),
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
