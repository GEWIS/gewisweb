<?php

namespace User\Mapper;

use Doctrine\ORM\EntityManager;

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
     * Counts the number of failed login attempts by a company.
     *
     * @param $since
     * @param $type
     * @param $ip
     * @param null $company
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCompanyFailedAttemptCount($since, $type, $ip, $company = null)
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

        if (!is_null($company)) {
            $qb->andWhere('a.company = :company')
                ->setParameter('company', $company);
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
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('User\Model\LoginAttempt');
    }
}
