<?php

declare(strict_types=1);

namespace App\Repository\Frontpage;

use App\Entity\Decision\Member;
use App\Entity\Frontpage\Poll;
use App\Entity\Frontpage\PollComment;
use App\Entity\Frontpage\PollCommentReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function intval;

/**
 * @extends ServiceEntityRepository<PollCommentReaction>
 */
class PollCommentReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            PollCommentReaction::class,
        );
    }

    /**
     * How this member responded to a comment, if they did. One reaction per member per comment is a database
     * constraint, so there is never more than one to find.
     */
    public function findOneByCommentAndMember(
        PollComment $comment,
        Member $member,
    ): ?PollCommentReaction {
        return $this->findOneBy(
            [
                'comment' => $comment->getId(),
                'member' => $member->getLidnr(),
            ],
        );
    }

    /**
     * Takes the member off every reaction underneath one poll in a single statement, leaving the counts under each
     * comment as they were. The reactions are changed in the database rather than in the entity manager, so anything
     * holding one is out of date afterwards.
     */
    public function anonymiseForPoll(Poll $poll): int
    {
        return intval($this->createQueryBuilder('r')
            ->update()
            ->set(
                'r.member',
                'NULL',
            )
            ->where('r.member IS NOT NULL')
            ->andWhere(
                'r.comment IN (SELECT c.id FROM ' . PollComment::class . ' c WHERE IDENTITY(c.poll) = :poll)',
            )
            ->setParameter(
                'poll',
                $poll->getId(),
            )
            ->getQuery()
            ->execute());
    }
}
