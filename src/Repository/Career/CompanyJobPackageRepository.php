<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyJobPackage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyJobPackage>
 */
class CompanyJobPackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyJobPackage::class,
        );
    }

    /**
     * Get all job packages for a specific company.
     *
     * @return CompanyJobPackage[]
     */
    public function findJobPackagesByCompany(Company $company): array
    {
        $qb = $this->createQueryBuilder('p');
        $qb->where('p.company = :company')
            ->setParameter(
                'company',
                $company,
                Company::class,
            );

        return $qb->getQuery()->getResult();
    }
}
