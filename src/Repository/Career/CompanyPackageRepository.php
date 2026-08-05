<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyBannerPackage;
use App\Entity\Career\CompanyFeaturedPackage;
use App\Entity\Career\CompanyHighlightPackage;
use App\Entity\Career\CompanyJobPackage;
use App\Entity\Career\CompanyPackage;
use App\Entity\Career\Enums\CompanyPackageTypes;
use DateTime;
use DateTimeImmutable;
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
     * @phpstan-return (
     *     $companyPackageType is CompanyPackageTypes::Banner
     *     ? CompanyBannerPackage[]
     *     : (
     *         $companyPackageType is CompanyPackageTypes::Featured
     *         ? CompanyFeaturedPackage[]
     *         : (
     *             $companyPackageType is CompanyPackageTypes::Highlight
     *             ? CompanyHighlightPackage[]
     *             : CompanyJobPackage[]
     *         )
     *     )
     * )
     */
    public function findFuturePackageExpirationsBeforeDate(
        CompanyPackageTypes $companyPackageType,
        DateTime $date,
    ): array {
        $companyPackageClass = CompanyPackageTypes::entityClass($companyPackageType);

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
     * @phpstan-return (
     *     $companyPackageType is CompanyPackageTypes::Banner
     *     ? CompanyBannerPackage[]
     *     : (
     *         $companyPackageType is CompanyPackageTypes::Featured
     *         ? CompanyFeaturedPackage[]
     *         : (
     *             $companyPackageType is CompanyPackageTypes::Highlight
     *             ? CompanyHighlightPackage[]
     *             : CompanyJobPackage[]
     *         )
     *     )
     * )
     */
    public function findFuturePackageStartsBeforeDate(
        CompanyPackageTypes $companyPackageType,
        DateTime $date,
    ): array {
        $companyPackageClass = CompanyPackageTypes::entityClass($companyPackageType);

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
     * The banner packages with a proposal waiting for the committee, oldest first so nothing sits unanswered.
     *
     * @return list<CompanyBannerPackage>
     */
    public function findPendingBanners(): array
    {
        // The queue names the company and whoever put the banner forward, so both come along with the packages.
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'p',
                'c',
                's',
            )
            ->from(
                CompanyBannerPackage::class,
                'p',
            )
            ->join(
                'p.company',
                'c',
            )
            ->leftJoin(
                'p.pendingImageSubmittedBy',
                's',
            )
            ->where('p.pendingImage IS NOT NULL')
            ->orderBy(
                'p.pendingImageSubmittedAt',
                'ASC',
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
     */
    public function findNonExpiredPackages(Company $company): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.company = :company')
            ->andWhere('p.expires > CURRENT_DATE()')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Whether the company still has a contract to stand on: a package of any type that has not expired yet. A package
     * that has not started counts, so a company can prepare its profile in the run-up to its contract.
     */
    public function hasNonExpiredPackage(
        Company $company,
        DateTimeImmutable $now,
    ): bool {
        $qb = $this->createQueryBuilder('p');
        $qb->select('COUNT(p.id)')
            ->where('p.company = :company')
            ->andWhere('p.expires > :now')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            )
            ->setParameter(
                'now',
                $now,
                Types::DATETIME_IMMUTABLE,
            );

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
