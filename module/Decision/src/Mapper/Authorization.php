<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Decision\Model\{
    Authorization as AuthorizationModel,
    Member as MemberModel,
};

/**
 * Mappers for authorizations.
 */
class Authorization extends BaseMapper
{
    /**
     * Find authorizations for a meeting.
     *
     * @param int $meetingNumber
     *
     * @return array
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
     *
     * @param int $meetingNumber
     * @param MemberModel $authorizer
     *
     * @return AuthorizationModel|null
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
     * @param int $meetingNumber
     * @param MemberModel $recipient
     *
     * @return array
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

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return AuthorizationModel::class;
    }
}
