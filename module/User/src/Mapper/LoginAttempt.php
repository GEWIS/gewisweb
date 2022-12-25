<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use DateTime;
use User\Model\{
    CompanyUser as CompanyUserModel,
    LoginAttempt as LoginAttemptModel,
    User as UserModel,
};

class LoginAttempt extends BaseMapper
{
    public function getFailedAttemptCount(
        DateTime $since,
        string $ip,
        CompanyUserModel|UserModel|null $user = null,
    ): int|string {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(a.id)')
            ->from($this->getRepositoryName(), 'a')
            ->where('a.time > :since')
            ->andWhere('a.ip = :ip')
            ->setParameter('since', $since)
            ->setParameter('ip', $ip);

        if ($user instanceof CompanyUserModel) {
            $qb->andWhere('a.companyUser = :user')
                ->setParameter('user', $user);
        } else if ($user instanceof UserModel) {
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
