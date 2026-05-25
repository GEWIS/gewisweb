<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Signup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signup>
 */
class SignupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Signup::class,
        );
    }

    /**
     * Get all sign-ups for activities that ended 5 years ago.
     *
     * @return Signup[]
     */
    public function getSignupsOlderThan5Years(): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->join(
            's.signupList',
            'l',
        )
            ->join(
                'l.activity',
                'a',
            )
            ->where('a.endTime <= DATE_SUB(CURRENT_TIMESTAMP(), 5, "YEAR")');

        return $qb->getQuery()->getResult();
    }
}
