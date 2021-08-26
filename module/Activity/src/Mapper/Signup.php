<?php

namespace Activity\Mapper;

use Activity\Model\Signup as SignupModel;
use Application\Mapper\BaseMapper;

class Signup extends BaseMapper
{
    /**
     * @param int $id
     *
     * @return SignupModel
     */
    public function getSignupById($id)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\Signup', 'a')
            ->where('a.id = :id')
            ->setParameter('id', $id);
        $result = $qb->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }

    /**
     * Check if a user is signed up for an activity.
     *
     * @param int $activityId
     * @param int $userId
     *
     * @return bool
     */
    public function isSignedUp($activityId, $userId)
    {
        return null !== $this->getSignUp($activityId, $userId);
    }

    /**
     * Get the signup object for an usedid/activityid if it exists.
     *
     * @param int $signupListId
     * @param int $userId
     *
     * @return SignupModel
     */
    public function getSignUp($signupListId, $userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\UserSignup', 'a')
            ->join('a.user', 'u')
            ->where('u.lidnr = ?1')
            ->join('a.signupList', 'ac')
            ->andWhere('ac.id = ?2')
            ->setParameters(
                [
                    1 => $userId,
                    2 => $signupListId,
                ]
            );
        $result = $qb->getQuery()->getResult();

        return $result[0] ?? null;
    }

    /**
     * Get all activities which a user is signed up for.
     *
     * @param int $userId
     *
     * @return SignupModel
     */
    public function getSignedUpActivities($userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\UserSignup', 'a')
            ->join('a.user', 'u')
            ->where('u.lidnr = ?1')
            ->setParameter(1, $userId);

        return $qb->getQuery()->getResult();
    }

    public function getNumberOfSignedUpMembers($signupListId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(s)')
            ->from('Activity\Model\UserSignup', 's')
            ->join('s.signupList', 'a')
            ->where('a.id = ?1')
            ->setParameter(1, $signupListId);
        $result = $qb->getQuery()->getResult();

        return $result[0];
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return SignupModel::class;
    }
}
