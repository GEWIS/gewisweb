<?php

declare(strict_types=1);

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\Authorization as AuthorizationModel;
use Decision\Model\Member as MemberModel;

/**
 * Mappers for authorizations.
 *
 * @template-extends BaseMapper<AuthorizationModel>
 */
class Authorization extends BaseMapper
{
    /**
     * Find authorizations for a meeting.
     *
     * @return AuthorizationModel[]
     */
    public function findAllByType(
        int $meetingNumber,
        bool $revoked = false,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->setParameter('meetingNumber', $meetingNumber);

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
        MemberModel $authorizer,
    ): ?AuthorizationModel {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.authorizer = :authorizer')
            ->andWhere('a.revokedAt IS NULL')
            ->setParameter('meetingNumber', $meetingNumber)
            ->setParameter('authorizer', $authorizer);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find non-revoked authorizations for a meeting for a recipient.
     *
     * @return AuthorizationModel[]
     */
    public function findRecipientAuthorization(
        int $meetingNumber,
        MemberModel $recipient,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.recipient = :recipient')
            ->andWhere('a.revokedAt IS NULL')
            ->setParameter('meetingNumber', $meetingNumber)
            ->setParameter('recipient', $recipient);

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return AuthorizationModel::class;
    }
}
