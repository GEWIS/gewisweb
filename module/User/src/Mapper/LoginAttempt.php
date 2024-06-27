<?php

declare(strict_types=1);

namespace User\Mapper;

use Application\Mapper\BaseMapper;
use Application\Model\IdentityInterface;
use DateInterval;
use DateTime;
use Decision\Model\Member as MemberModel;
use User\Model\CompanyUser as CompanyUserModel;
use User\Model\LoginAttempt as LoginAttemptModel;
use User\Model\User as UserModel;

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
     * @return LoginAttemptModel[]
     */
    public function getAttemptsByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('l');
        $qb->where('l.user = :user')
            ->orderBy('l.time', 'DESC')
            ->setParameter('user', $member->getLidnr());

        return $qb->getQuery()->getResult();
    }

    protected function getRepositoryName(): string
    {
        return LoginAttemptModel::class;
    }

    /**
     * Delete all (failed) login attempts older than 3 months.
     */
    public function deleteLoginAttemptsOtherThan3Months(): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getRepositoryName(), 'l')
            ->where('l.time <= :date');

        $qb->setParameter('date', (new DateTime())->sub(new DateInterval('P3M')));

        $qb->getQuery()->execute();
    }
}
