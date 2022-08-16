<?php

namespace Activity\Mapper;

use Activity\Model\{
    ExternalSignup as ExternalSignupModel,
    SignupList as SignupListModel,
    UserSignup as UserSignupModel,
};
use Application\Mapper\BaseMapper;
use User\Model\User as UserModel;

class Signup extends BaseMapper
{
    /**
     * Check if a user is signed up for an activity.
     *
     * @param SignupListModel $signupList
     * @param UserModel $user
     *
     * @return bool
     */
    public function isSignedUp(
        SignupListModel $signupList,
        UserModel $user,
    ): bool {
        return null !== $this->getSignUp($signupList, $user);
    }

    /**
     * Get the signup object if it exists.
     *
     * @param SignupListModel $signupList
     * @param UserModel $user
     *
     * @return UserSignupModel|null
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
                ]
            );
        $result = $qb->getQuery()->getResult();

        return $result[0] ?? null;
    }

    public function getExternalSignUp(int $signupId): ?ExternalSignupModel
    {
        return $this->getEntityManager()->getRepository(ExternalSignupModel::class)->find($signupId);
    }

    /**
     * @param SignupListModel $signupList
     *
     * @return array
     */
    public function getNumberOfSignedUpMembers(SignupListModel $signupList): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(s)')
            ->from($this->getRepositoryName(), 's')
            ->where('s.signupList = :signupList')
            ->setParameter('signupList', $signupList);
        $result = $qb->getQuery()->getResult();

        return $result[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return UserSignupModel::class;
    }
}
