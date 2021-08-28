<?php

namespace Activity\Mapper;

use Activity\Model\SignupList as SignupListModel;
use Application\Mapper\BaseMapper;

class SignupList extends BaseMapper
{
    /**
     * @param int $signupListId
     * @param int $activityId
     *
     * @return SignupListModel|null
     */
    public function getSignupListByIdAndActivity($signupListId, $activityId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\SignupList', 'a')
            ->where('a.id = :signupList')
            ->andWhere('a.activity = :activity')
            ->setParameter('signupList', $signupListId)
            ->setParameter('activity', $activityId);
        $result = $qb->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }

    public function getSignupListsOfActivity($activityId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Activity\Model\SignupList', 'a')
            ->where('a.activity = :activity')
            ->setParameter('activity', $activityId);

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return SignupListModel::class;
    }
}
