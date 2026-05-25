<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\CompanyFeaturedPackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function array_rand;

/**
 * @extends ServiceEntityRepository<CompanyFeaturedPackage>
 */
class CompanyFeaturedPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyFeaturedPackage::class,
        );
    }

    /**
     * Returns a random featured package from the active featured packages,
     * and null when there is no featured package.
     */
    public function getFeaturedPackage(): ?CompanyFeaturedPackage
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.published = 1')
            ->andWhere('p.starts <= CURRENT_DATE()')
            ->andWhere('p.expires > CURRENT_DATE()');

        $featuredPackages = $qb->getQuery()->getResult();

        if ([] !== $featuredPackages) {
            return $featuredPackages[array_rand($featuredPackages)];
        }

        return null;
    }
}
