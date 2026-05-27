<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\Enums\CompanyPackageTypes;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function count;

/**
 * @extends ServiceEntityRepository<CompanyPackage>
 */
class CompanyPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyPackage::class,
        );
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     *
     * @psalm-return (
     *     $companyPackageType is CompanyPackageTypes::Banner
     *     ? CompanyBannerPackage[]
     *     : (
     *         $companyPackageType is CompanyPackageTypes::Featured
     *         ? CompanyFeaturedPackage[]
     *         : CompanyJobPackage[]
     *     )
     * )
     */
    public function findFuturePackageExpirationsBeforeDate(
        CompanyPackageTypes $companyPackageType,
        DateTime $date,
    ): array {
        $companyPackageClass = $this->resolvePackageClass($companyPackageType);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')
            ->from(
                $companyPackageClass,
                'p',
            )
            ->where('p.published = 1')
            // All packages that will expire between today and then, ordered smallest first
            ->andWhere('p.expires > CURRENT_DATE()')
            ->andWhere('p.expires <= :date')
            ->orderBy(
                'p.expires',
                'ASC',
            )
            ->setParameter(
                'date',
                $date,
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Will return a list of published packages that will expire between now and $date.
     *
     * @param DateTime $date The date until where to search
     *
     * @psalm-return (
     *     $companyPackageType is CompanyPackageTypes::Banner
     *     ? CompanyBannerPackage[]
     *     : (
     *         $companyPackageType is CompanyPackageTypes::Featured
     *         ? CompanyFeaturedPackage[]
     *         : CompanyJobPackage[]
     *     )
     * )
     */
    public function findFuturePackageStartsBeforeDate(
        CompanyPackageTypes $companyPackageType,
        DateTime $date,
    ): array {
        $companyPackageClass = $this->resolvePackageClass($companyPackageType);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p')
            ->from(
                $companyPackageClass,
                'p',
            )
            ->where('p.published = 1')
            // All packages that will start between today and then, ordered smallest first
            ->andWhere('p.starts > CURRENT_DATE()')
            ->andWhere('p.starts <= :date')
            ->orderBy(
                'p.starts',
                'ASC',
            )
            ->setParameter(
                'date',
                $date,
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all packages, and returns an editable version of them.
     */
    public function findEditablePackage(int $packageId): ?CompanyPackage
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.id = :packageId')
            ->setParameter(
                'packageId',
                $packageId,
                Types::INTEGER,
            )
            ->setMaxResults(1);

        $packages = $qb->getQuery()->getResult();

        if (1 !== count($packages)) {
            return null;
        }

        return $packages[0];
    }

    /**
     * Get non-expired packages for a specific company.
     *
     * @return list<CompanyPackage>
     *
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType Doctrine getResult() is mixed to Psalm.
     */
    public function findNonExpiredPackages(Company $company): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.company = :company')
            ->andWhere('p.expires > CURRENT_DATE()')
            ->setParameter(
                'company',
                $company,
                Company::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * @psalm-return (
     *     $type is CompanyPackageTypes::Banner
     *     ? class-string<CompanyBannerPackage>
     *     : (
     *         $type is CompanyPackageTypes::Featured
     *         ? class-string<CompanyFeaturedPackage>
     *         : class-string<CompanyJobPackage>
     *     )
     * )
     */
    private function resolvePackageClass(CompanyPackageTypes $type): string
    {
        return match ($type) {
            CompanyPackageTypes::Banner => CompanyBannerPackage::class,
            CompanyPackageTypes::Featured => CompanyFeaturedPackage::class,
            CompanyPackageTypes::Job => CompanyJobPackage::class,
        };
    }
}
