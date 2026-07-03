<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\SignupList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExternalSignup>
 */
class ExternalSignupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ExternalSignup::class,
        );
    }

    /**
     * An existing external sign-up on a list for the given email (case-insensitive), if any. Prevents a second row for
     * the same address: a repeat submission re-sends verification instead.
     */
    public function findOneByListAndEmail(
        SignupList $signupList,
        string $email,
    ): ?ExternalSignup {
        return $this->createQueryBuilder('e')
            ->where('e.signupList = :list')
            ->andWhere('LOWER(e.email) = LOWER(:email)')
            ->setParameter(
                'list',
                $signupList->getId(),
                Types::INTEGER,
            )
            ->setParameter(
                'email',
                $email,
            )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
