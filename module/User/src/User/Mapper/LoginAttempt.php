<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class LoginAttempt
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

    public function getFailedAttemptCount($since, $type, $ip, $user = null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(a)')
            ->from('User\Model\LoginAttempt', 'a')
            ->where('a.type = :type')
            ->andWhere('a.time > :since')
            ->andWhere('a.ip = :ip')
            ->setParameter('type', $type)
            ->setParameter('since', $since)
            ->setParameter('ip', $ip);

        if (!is_null($user)) {
            $qb->andWhere('a.user = :user')
                ->setParameter('user', $user);
        }
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Persist a login attempt model
     */
    public function persist($loginAttempt)
    {
        $this->em->persist($loginAttempt);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     *
     * @return EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\LoginAttempt');
    }
}
