<?php

namespace Decision\Mapper;

use Application\Mapper\BaseMapper;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Decision\Model\Authorization as AuthorizationModel;

/**
 * Mappers for authorizations.
 */
class Authorization extends BaseMapper
{
    /**
     * Find all authorizations for a meeting.
     *
     * @param int $meetingNumber
     *
     * @return array
     */
    public function findNotRevoked($meetingNumber)
    {
        return $this->getRepository()->findBy(['meetingNumber' => $meetingNumber, 'revoked' => false]);
    }

    /**
     * Find non-revoked authorizations for a meeting for a user.
     *
     * @param int $meetingNumber
     * @param int $authorizer
     *
     * @return \Decision\Model\Authorization|null
     */
    public function findUserAuthorization($meetingNumber, $authorizer)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.authorizer = :authorizer')
            ->andWhere('a.revoked = 0')
            ->setParameter('meetingNumber', $meetingNumber)
            ->setParameter('authorizer', $authorizer);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find non-revoked authorizations for a meeting for a recipient.
     *
     * @param int $meetingNumber
     * @param int $recipient
     *
     * @return array
     */
    public function findRecipientAuthorization($meetingNumber, $recipient)
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('a')
            ->from($this->getRepositoryName(), 'a')
            ->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.recipient = :recipient')
            ->andWhere('a.revoked = 0')
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
