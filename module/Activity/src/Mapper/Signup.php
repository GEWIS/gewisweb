<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\ExternalSignup as ExternalSignupModel;
use Activity\Model\Signup as SignupModel;
use Activity\Model\SignupList as SignupListModel;
use Activity\Model\UserSignup as UserSignupModel;
use Application\Mapper\BaseMapper;
use DateInterval;
use DateTime;
use Decision\Model\Member as MemberModel;
use User\Model\User as UserModel;

/**
 * @template-extends BaseMapper<UserSignupModel>
 */
class Signup extends BaseMapper
{
    /**
     * Check if a user is signed up for an activity.
     */
    public function isSignedUp(
        SignupListModel $signupList,
        UserModel $user,
    ): bool {
        return null !== $this->getSignUp($signupList, $user);
    }

    /**
     * Get the signup object if it exists.
     */
    public function getSignUp(
        SignupListModel $signupList,
        UserModel $user,
    ): ?UserSignupModel {
        $qb = $this->getRepository()->createQueryBuilder('s');
        $qb->where('s.signupList = :signupList')
            ->andWhere('s.user = :user')
            ->setParameters(
                [
                    'signupList' => $signupList,
                    'user' => $user,
                ],
            );
        $result = $qb->getQuery()->getResult();

        return $result[0] ?? null;
    }

    public function getExternalSignUp(int $signupId): ?ExternalSignupModel
    {
        return $this->getEntityManager()->getRepository(ExternalSignupModel::class)->find($signupId);
    }

    public function getNumberOfSignedUpMembers(SignupListModel $signupList): string|int|float|bool|null
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(s)')
            ->from($this->getRepositoryName(), 's')
            ->where('s.signupList = :signupList')
            ->setParameter('signupList', $signupList);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get all sign-ups for a specific member.
     *
     * @return UserSignupModel[]
     */
    public function findSignupsByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('s');
        $qb->where('s.user = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all sign-ups for activities that ended 5 years ago.
     *
     * @return SignupModel[]
     */
    public function getSignupsOlderThan5Years(): array
    {
        $qb = $this->getRepository()->createQueryBuilder('s');
        $qb->join('s.signupList', 'l')
            ->join('l.activity', 'a')
            ->where('a.endTime <= :date');

        $qb->setParameter('date', (new DateTime())->sub(new DateInterval('P5Y')));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get Signup by id
     */
    public function getSignupById(int $id): ?SignupModel
    {
        return $this->getEntityManager()->getRepository(SignupModel::class)->find($id);
    }

    protected function getRepositoryName(): string
    {
        return UserSignupModel::class;
    }
}
