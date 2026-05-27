<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Authorization;
use App\Entity\Decision\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Authorization>
 */
class AuthorizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Authorization::class,
        );
    }

    /**
     * Find authorizations for a meeting.
     *
     * @return Authorization[]
     */
    public function findAllByType(
        int $meetingNumber,
        bool $revoked = false,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->setParameter(
                'meetingNumber',
                $meetingNumber,
            );

        if ($revoked) {
            $qb->andWhere('a.revokedAt IS NOT NULL');
        } else {
            $qb->andWhere('a.revokedAt IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find non-revoked authorizations for a meeting for a user.
     */
    public function findUserAuthorization(
        int $meetingNumber,
        Member $authorizer,
    ): ?Authorization {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.authorizer = :authorizer')
            ->andWhere('a.revokedAt IS NULL')
            ->setParameter(
                'meetingNumber',
                $meetingNumber,
            )
            ->setParameter(
                'authorizer',
                $authorizer,
                Member::class,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find non-revoked authorizations for a meeting for a recipient.
     *
     * @return Authorization[]
     */
    public function findRecipientAuthorization(
        int $meetingNumber,
        Member $recipient,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.recipient = :recipient')
            ->andWhere('a.revokedAt IS NULL')
            ->setParameter(
                'meetingNumber',
                $meetingNumber,
            )
            ->setParameter(
                'recipient',
                $recipient,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all authorizations granted by or granted to a specific member. This includes revoked authorizations.
     *
     * @return Authorization[]
     */
    public function findByMember(
        Member $member,
        bool $authorizer,
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($authorizer) {
            $qb->where('a.authorizer = :authorizer')
                ->setParameter(
                    'authorizer',
                    $member,
                    Member::class,
                );
        } else {
            $qb->where('a.recipient = :recipient')
                ->setParameter(
                    'recipient',
                    $member,
                    Member::class,
                );
        }

        return $qb->getQuery()->getResult();
    }
}
