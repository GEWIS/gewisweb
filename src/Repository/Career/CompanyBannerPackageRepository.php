<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\CompanyBannerPackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_rand;

/**
 * @extends ServiceEntityRepository<CompanyBannerPackage>
 */
class CompanyBannerPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyBannerPackage::class,
        );
    }

    /**
     * Returns a random banner from the active banners.
     */
    public function getBannerPackage(): ?CompanyBannerPackage
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.published = 1')
            ->andWhere('p.starts <= CURRENT_DATE()')
            ->andWhere('p.expires > CURRENT_DATE()');

        $banners = $qb->getQuery()->getResult();

        if ([] !== $banners) {
            return $banners[array_rand($banners)];
        }

        return null;
    }
}
