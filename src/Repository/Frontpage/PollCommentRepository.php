<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\PollComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PollComment>
 */
class PollCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollComment::class,
        );
    }

    /**
     * Get all poll comments made by specific member.
     *
     * @return PollComment[]
     */
    public function findByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.user = :member')
            ->orderBy(
                'c.createdOn',
                'DESC',
            )
            ->setParameter(
                'member',
                $member->getLidnr(),
            );

        return $qb->getQuery()->getResult();
    }
}
