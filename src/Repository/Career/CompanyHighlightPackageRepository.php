<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\CompanyHighlightPackage;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyHighlightPackage>
 */
class CompanyHighlightPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyHighlightPackage::class,
        );
    }

    /**
     * Every highlight package running right now, for the landing page. Whether a package's individual picks are still
     * showable is settled by the package itself.
     *
     * @return list<CompanyHighlightPackage>
     */
    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.published = true')
            ->andWhere('p.starts <= :now')
            ->andWhere('p.expires > :now')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }
}
