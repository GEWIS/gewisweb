<?php

declare(strict_types=1);

namespace Company\Mapper;

use Application\Mapper\BaseMapper;
use Company\Model\Company as CompanyModel;
use Company\Model\CompanyBannerPackage as CompanyBannerPackageModel;
use Company\Model\CompanyFeaturedPackage as CompanyFeaturedPackageModel;
use Company\Model\CompanyJobPackage as CompanyJobPackageModel;
use Company\Model\CompanyPackage as CompanyPackageModel;
use Company\Model\Enums\CompanyPackageTypes;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Override;

use function count;

/**
 * Mappers for package.
 *
 * @template T0 of object
 *
 * @template-extends BaseMapper<T0>
 */
class Package extends BaseMapper
{
    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     *
     * @return T0[]
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
     * @return T0[]
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
     * @return T0[]
     */
    public function findVisiblePackages(): array
    {
        $qb = $this->getVisiblePackagesQueryBuilder();

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all packages, and returns an editable version of them.
     */
    public function findEditablePackage(int $packageId): ?CompanyPackageModel
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.id=:packageId')
            ->setParameter('packageId', $packageId)
            ->setMaxResults(1);

        $packages = $qb->getQuery()->getResult();

        if (1 !== count($packages)) {
            return null;
        }

        return $packages[0];
    }

    /**
     * Get all job packages for a specific company.
     *
     * @return CompanyJobPackageModel[]
     */
    public function findJobPackagesByCompany(CompanyModel $company): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.company = :company')
            ->setParameter('company', $company);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get non-expired packages for a specific company.
     *
     * @return T0[]
     */
    public function findNonExpiredPackages(CompanyModel $company): array
    {
        $qb = $this->getRepository()->createQueryBuilder('p');
        $qb->where('p.company = :company')
            ->andWhere('p.expires > CURRENT_DATE()')
            ->setParameter('company', $company);

        return $qb->getQuery()->getResult();
    }

    public function createPackage(CompanyPackageTypes $type): CompanyPackageModel
    {
        return match ($type) {
            CompanyPackageTypes::Banner => new CompanyBannerPackageModel(),
            CompanyPackageTypes::Featured => new CompanyFeaturedPackageModel(),
            CompanyPackageTypes::Job => new CompanyJobPackageModel(),
        };
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return CompanyJobPackageModel::class;
    }
}
