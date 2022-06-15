<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use User\Model\{
    LoginAttempt as LoginAttemptModel,
    User as UserModel,
};

class LoginAttempt extends BaseMapper
{
    /**
     * @param DateTime $since
     * @param string $ip
     * @param UserModel|null $user
     *
     * @return int|string
     */
    public function getFailedAttemptCount(
        DateTime $since,
        string $ip,
        ?UserModel $user = null,
    ): int|string {
        $qb = $this->em->createQueryBuilder();
        $qb->select('count(a)')
            ->from($this->getRepositoryName(), 'a')
            ->where('a.time > :since')
            ->andWhere('a.ip = :ip')
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
        return LoginAttemptModel::class;
    }
}
