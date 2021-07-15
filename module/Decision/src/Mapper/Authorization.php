<?php

namespace Decision\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mappers for authorizations.
 */
class Authorization
{
    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find all authorizations for a meeting.
     *
     * @param int $meetingNumber
     *
     * @return array
     */
    public function find($meetingNumber)
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
            ->from('Decision\Model\Authorization', 'a')
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
            ->from('Decision\Model\Authorization', 'a')
            ->where('a.meetingNumber = :meetingNumber')
            ->andWhere('a.recipient = :recipient')
            ->andWhere('a.revoked = 0')
            ->setParameter('meetingNumber', $meetingNumber)
            ->setParameter('recipient', $recipient);

        return $qb->getQuery()->getResult();
    }

    public function persist($authorization)
    {
        $this->em->persist($authorization);
        $this->em->flush();
    }

    public function flush()
    {
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Authorization');
    }
}
