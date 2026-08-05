<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyAuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyAuditLog>
 */
class CompanyAuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyAuditLog::class,
        );
    }

    /**
     * @return list<CompanyAuditLog>
     */
    public function findRecentForCompany(
        Company $company,
        int $limit = 25,
    ): array {
        $qb = $this->createQueryBuilder('log')
            ->select(
                'log',
                'actor',
                'actorCompanyUser',
            )
            ->leftJoin(
                'log.actor',
                'actor',
            )
            ->leftJoin(
                'log.actorCompanyUser',
                'actorCompanyUser',
            )
            ->where('log.company = :company')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'log.createdAt',
                'DESC',
            )
            ->addOrderBy(
                'log.id',
                'DESC',
            )
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
