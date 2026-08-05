<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\CompanyBannerPackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function shuffle;

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
     * The banners that may be shown right now: published, inside their window, and carrying an image the committee
     * agreed to.
     *
     * They come back shuffled. Whoever is first is the one seen by everybody who does not wait for the carousel to
     * move on, and that should not be decided by whose contract was signed first.
     *
     * @return list<CompanyBannerPackage>
     */
    public function findActiveBanners(): array
    {
        // The banner links through to the company it belongs to, so that comes along with it.
        $qb = $this->createQueryBuilder('p')
            ->addSelect('c')
            ->join(
                'p.company',
                'c',
            )
            ->where('p.published = true')
            ->andWhere('p.starts <= CURRENT_DATE()')
            ->andWhere('p.expires > CURRENT_DATE()')
            ->andWhere('p.image IS NOT NULL');

        $banners = $qb->getQuery()->getResult();
        shuffle($banners);

        return $banners;
    }
}
