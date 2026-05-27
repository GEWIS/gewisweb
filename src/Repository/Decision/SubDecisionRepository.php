<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Member;
use App\Entity\Decision\SubDecision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;

/**
 * @extends ServiceEntityRepository<SubDecision>
 */
class SubDecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            SubDecision::class,
        );
    }

    /**
     * Search sub-decisions.
     *
     * @return SubDecision[]
     */
    public function findByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.contentNL LIKE :full_name')
            ->orWhere('s.member = :member');

        $qb->setParameter(
            'full_name',
            '%' . addcslashes(
                $member->getFullName(),
                '%_',
            ) . '%',
        )
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }
}
