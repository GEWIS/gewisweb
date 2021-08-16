<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;

class LoginAttempt extends BaseMapper
{
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
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return 'User\Model\LoginAttempt';
    }
}
