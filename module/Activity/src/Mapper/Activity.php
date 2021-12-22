<?php

namespace Activity\Mapper;

use Activity\Model\Activity as ActivityModel;
use Application\Mapper\BaseMapper;
use DateTime;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use User\Model\User as UserModel;

class Activity extends BaseMapper
{
    /**
     * Get upcoming activities sorted by date.
     *
     * @param int|null $count optional number of activities to retrieve
     * @param OrganModel|null $organ option organ by whom the activities are organized
     * @param string|null $category
     *
     * @return array
     */
    public function getUpcomingActivities(
        int $count = null,
        OrganModel $organ = null,
        string $category = null,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->andWhere('a.status = :status')
            ->orderBy('a.beginTime', 'ASC');

        if (!is_null($count)) {
            $qb->setMaxResults($count);
        }

        if (!is_null($organ)) {
            $qb->andWhere('a.organ = :organ')
                ->setParameter('organ', $organ);
        }

        // For now 'career' is the only category, however this may change in the future
        if ('career' === $category) {
            $qb->andWhere('a.isMyFuture = 1');
        }
        $qb->setParameter('now', new DateTime());
        $qb->setParameter('status', ActivityModel::STATUS_APPROVED);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date for member.
     *
     * @param UserModel $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesForMember(UserModel $user): array
    {
        // Get subscriptions (not including non-approved)
        $result = $this->getUpcomingActivitiesSubscribedBy($user);

        // Get created by member (including non-approved)
        $result = array_merge($result, $this->getUpcomingActivitiesCreatedBy($user));

        // Get associated with organs (including non-approved)
        foreach ($user->getMember()->getCurrentOrganInstallations() as $organMember) {
            $result = array_merge($result, $this->getUpcomingActivitiesByOrgan($organMember->getOrgan()));
        }

        // Do sorting based on start time
        usort(
            $result,
            function ($a, $b) {
                $beginA = $a->getBeginTime();
                $beginB = $b->getBeginTime();

                return $beginA < $beginB ? -1 : 1;
            }
        );

        $size = count($result);

        for ($i = 0; $i < $size; ++$i) {
            for ($j = $i + 1; $j < $size; ++$j) {
                if (
                    array_key_exists($i, $result)
                    && array_key_exists($j, $result)
                    && $result[$i]->getId() == $result[$j]->getId()
                ) {
                    unset($result[$j]);
                }
            }
        }

        return $result;
    }

    /**
     * Get upcoming activities sorted by date that a user is subscribed to.
     *
     * @param UserModel $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesSubscribedBy(UserModel $user): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->from('Activity\Model\SignupList', 'b')
            ->from('Activity\Model\UserSignup', 'c')
            ->where('a.endTime > :now')
            ->setParameter('now', new DateTime())
            ->andWhere('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->andWhere('a = b.activity')
            ->andWhere('b = c.signupList')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date that a user created.
     *
     * @param UserModel $user Option user that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesCreatedBy(UserModel $user): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->setParameter('now', new DateTime())
            ->andWhere('a.creator = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date that a organ created.
     *
     * @param OrganModel $organ Option organ that should relate to activity
     *
     * @return array
     */
    public function getUpcomingActivitiesByOrgan(OrganModel $organ): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->from($this->getRepositoryName(), 'a')
            ->where('a.endTime > :now')
            ->setParameter('now', new DateTime())
            ->andWhere('a.organ = :organ')
            ->setParameter('organ', $organ);

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets upcoming activities of the given organs or user, sorted by date.
     *
     * @param array|null $organs
     * @param UserModel|null $user
     * @param int|null $status An optional filter for activity status
     *
     * @return array
     */
    public function getAllUpcomingActivities(
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): array {
        $qb = $this->activityByOrganizerQuery(
            $this->em->createQueryBuilder()->expr()->gt('a.endTime', ':now'),
            $organs,
            $user,
            $status
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Comparison|null $filter
     * @param array|null $organs
     * @param UserModel|null $user
     * @param int|null $status
     *
     * @return QueryBuilder
     */
    protected function activityByOrganizerQuery(
        ?Comparison $filter = null,
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): QueryBuilder {
        $qb = $this->getRepository()->createQueryBuilder('a');

        if (!is_null($status)) {
            $qb->where('a.status = :status')
                ->setParameter('status', $status);
        } else {
            $qb->where('a.status <> :status')
                ->setParameter('status', ActivityModel::STATUS_UPDATE);
        }

        if (!is_null($filter)) {
            $qb->andWhere($filter)
                ->setParameter('now', new DateTime());
        }

        $qb->join('a.creator', 'u');

        if (!is_null($organs) && !is_null($user)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('a.organ', ':organs'),
                    'u.lidnr = :user'
                )
            )
                ->setParameter('organs', $organs)
                ->setParameter('user', $user);
        }

        $qb->orderBy('a.beginTime', 'DESC');

        return $qb;
    }

    /**
     * Gets a paginator of old activities of the given organs, sorted by date.
     * Supplying 'null' to all arguments gets all activities.
     *
     * @param array|null $organs
     * @param UserModel|null $user
     * @param int|null $status An optional filter for activity status
     *
     * @return DoctrineAdapter
     */
    public function getOldActivityPaginatorAdapterByOrganizer(
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): DoctrineAdapter {
        $qb = $this->activityByOrganizerQuery(
            $this->em->createQueryBuilder()->expr()->lt('a.endTime', ':now'),
            $organs,
            $user,
            $status
        );

        return new DoctrineAdapter(new ORMPaginator($qb));
    }

    /**
     * Returns the oldest activity that has taken place.
     *
     * @return ActivityModel|null
     */
    public function getOldestActivity(): ?ActivityModel
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->orderBy('a.beginTime', 'ASC')
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return empty($res) ? null : $res[0];
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return array
     */
    public function getArchivedActivitiesInRange(
        DateTime $start,
        DateTime $end,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.beginTime >= :start')
            ->setParameter('start', $start)
            ->andWhere('a.endTime <= :end')
            ->setParameter('end', $end)
            ->andWhere('a.status = :status')
            ->setParameter('status', ActivityModel::STATUS_APPROVED)
            ->orderBy('a.beginTime', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @inheritDoc
     */
    protected function getRepositoryName(): string
    {
        return ActivityModel::class;
    }
}
