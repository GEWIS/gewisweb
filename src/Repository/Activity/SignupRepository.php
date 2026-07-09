<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\ExternalSignup;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

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
     * The number of admitted (drawn) sign-ups on a list that count as confirmed subscribers: externals not yet
     * email-confirmed (null `verifiedAt`) are excluded, mirroring the draw's subscriber semantics
     * ({@see \App\Service\Activity\DrawManager}). This count continues the draw's capacity counter, so both must
     * read the same confirmation signal.
     */
    public function countConfirmedAdmitted(SignupList $signupList): int
    {
        return intval(
            $this->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->where('s.signupList = :list')
                ->andWhere('s.drawn = true')
                ->andWhere(
                    'NOT EXISTS ('
                    . 'SELECT 1 FROM ' . ExternalSignup::class . ' es'
                    . ' WHERE es.id = s.id AND es.verifiedAt IS NULL'
                    . ')',
                )
                ->setParameter(
                    'list',
                    $signupList->getId(),
                    Types::INTEGER,
                )
                ->getQuery()
                ->getSingleScalarResult(),
        );
    }

    /**
     * Get all sign-ups for activities that ended 5 years ago.
     *
     * @return Signup[]
     */
    public function getSignupsOlderThan5Years(): array
    {
        // The activity's schedule lives on its revision; a sign-up's list belongs to the activity's live revision
        // (sign-ups are migrated onto the newly-approved revision on every approval), so that revision's end time
        // defines when the activity ended.
        $qb = $this->createQueryBuilder('s');
        $qb->join(
            's.signupList',
            'l',
        )
            ->join(
                'l.revision',
                'r',
            )
            ->where("r.endTime <= DATE_SUB(CURRENT_TIMESTAMP(), 5, 'YEAR')");

        return $qb->getQuery()->getResult();
    }
}
