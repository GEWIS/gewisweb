<?php

namespace Decision\Mapper;

use Doctrine\ORM\EntityManager;

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
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find all authorizations for a meeting
     *
     * @param integer $meetingNumber
     *
     * @return array
     */
    public function find($meetingNumber)
    {
        return $this->getRepository()->findBy(['meetingNumber' => $meetingNumber, 'revoked' => false]);
    }

    /**
     * Find non-revoked authorizations for a meeting for a user
     *
     * @param integer $meetingNumber
     * @param integer $authorizer
     *
     * @return array
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
     * Find non-revoked authorizations for a meeting for a recipient
     *
     * @param integer $meetingNumber
     * @param integer $recipient
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
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('Decision\Model\Authorization');
    }
}
