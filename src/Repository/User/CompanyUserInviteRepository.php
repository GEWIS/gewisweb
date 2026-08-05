<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Career\Company;
use App\Entity\User\CompanyUserInvite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function strtolower;

/**
 * @extends ServiceEntityRepository<CompanyUserInvite>
 */
class CompanyUserInviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CompanyUserInvite::class,
        );
    }

    /**
     * Look up an invitation by its selector. The verifier must still be hash-compared against `getHashedToken()` with
     * `hash_equals`, and the expiry checked, before honouring it.
     */
    public function findBySelector(string $selector): ?CompanyUserInvite
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    public function findByEmail(string $email): ?CompanyUserInvite
    {
        $qb = $this->createQueryBuilder('i')
            ->where('LOWER(i.email) = :email')
            ->setParameter(
                'email',
                strtolower($email),
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return list<CompanyUserInvite>
     */
    public function findForCompany(Company $company): array
    {
        $qb = $this->createQueryBuilder('i')
            ->where('i.company = :company')
            ->setParameter(
                'company',
                $company->getId(),
                Types::INTEGER,
            )
            ->orderBy(
                'i.createdAt',
                'DESC',
            );

        return $qb->getQuery()->getResult();
    }
}
