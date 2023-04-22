<?php

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\IdentityInterface;
use DateTime;
use User\Model\{
    CompanyUser as CompanyUserModel,
    LoginAttempt as LoginAttemptModel,
    User as UserModel,
};

/**
 * @template-extends BaseMapper<LoginAttemptModel>
 */
class LoginAttempt extends BaseMapper
{
    public function getFailedAttemptCount(
        DateTime $since,
        string $ip,
        ?IdentityInterface $user = null,
    ): int {
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
        } elseif ($user instanceof UserModel) {
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
