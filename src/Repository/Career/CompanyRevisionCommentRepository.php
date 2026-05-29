<?php

declare(strict_types=1);

namespace App\Repository\Career;

use App\Entity\Career\Company;
use App\Entity\Career\CompanyRevisionComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyRevisionComment>
 */
class CompanyRevisionCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyRevisionComment::class,
        );
    }

    /**
     * The full review discussion across every revision of a company, oldest first.
     *
     * @return CompanyRevisionComment[]
     */
    public function findThreadForCompany(Company $company): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect(
                'au',
                'r',
            )
            ->join(
                'c.author',
                'au',
            )
            ->join(
                'c.revision',
                'r',
            )
            ->where('IDENTITY(r.company) = :companyId')
            ->setParameter(
                'companyId',
                $company->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'c.createdAt',
                'ASC',
            )
            ->getQuery()
            ->getResult();
    }
}
