<?php

declare(strict_types=1);

namespace Activity\Mapper;

use Activity\Model\Activity as ActivityModel;
use Activity\Model\SignupList;
use Activity\Model\UserSignup;
use Application\Mapper\BaseMapper;
use DateTime;
use Decision\Model\Member as MemberModel;
use Decision\Model\Organ as OrganModel;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Override;
use User\Model\User as UserModel;

use function array_key_exists;
use function array_merge;
use function count;
use function usort;

/**
 * @template-extends BaseMapper<ActivityModel>
 */
class Activity extends BaseMapper
{
    /**
     * Get upcoming activities sorted by date.
     *
     * @param int|null        $count optional number of activities to retrieve
     * @param OrganModel|null $organ option organ by whom the activities are organized
     *
     * @return ActivityModel[]
     */
    public function getUpcomingActivities(
        ?int $count = null,
        ?OrganModel $organ = null,
        ?string $category = null,
    ): array {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->andWhere('a.status = :status')
            ->orderBy('a.beginTime', 'ASC');

        if (null !== $count) {
            $qb->setMaxResults($count);
        }

        if (null !== $organ) {
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
     * @return ActivityModel[]
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
            static function ($a, $b) {
                $beginA = $a->getBeginTime();
                $beginB = $b->getBeginTime();

                return $beginA < $beginB ? -1 : 1;
            },
        );

        $size = count($result);

        for ($i = 0; $i < $size; ++$i) {
            for ($j = $i + 1; $j < $size; ++$j) {
                if (
                    !array_key_exists($i, $result)
                    || !array_key_exists($j, $result)
                    || $result[$i]->getId() !== $result[$j]->getId()
                ) {
                    continue;
                }

                unset($result[$j]);
            }
        }

        return $result;
    }

    /**
     * Get upcoming activities sorted by date that a user is subscribed to.
     *
     * @param UserModel $user Option user that should relate to activity
     *
     * @return ActivityModel[]
     */
    public function getUpcomingActivitiesSubscribedBy(UserModel $user): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->from(SignupList::class, 'b')
            ->from(UserSignup::class, 'c')
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
     * @return ActivityModel[]
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
     * Get upcoming activities sorted by date that an organ created.
     *
     * @param OrganModel $organ Option organ that should relate to activity
     *
     * @return ActivityModel[]
     */
    public function getUpcomingActivitiesByOrgan(OrganModel $organ): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->setParameter('now', new DateTime())
            ->andWhere('a.organ = :organ')
            ->setParameter('organ', $organ);

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets upcoming activities of the given organs or user, sorted by date.
     *
     * @param ?OrganModel[] $organs
     * @param int|null      $status An optional filter for activity status
     *
     * @return ActivityModel[]
     */
    public function getAllUpcomingActivities(
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): array {
        $qb = $this->activityByOrganizerQuery(
            $this->getEntityManager()->createQueryBuilder()->expr()->gt('a.endTime', ':now'),
            $organs,
            $user,
            $status,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ?OrganModel[] $organs
     */
    protected function activityByOrganizerQuery(
        ?Comparison $filter = null,
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): QueryBuilder {
        $qb = $this->getRepository()->createQueryBuilder('a');

        if (null !== $status) {
            $qb->where('a.status = :status')
                ->setParameter('status', $status);
        } else {
            $qb->where('a.status <> :status')
                ->setParameter('status', ActivityModel::STATUS_UPDATE);
        }

        if (null !== $filter) {
            $qb->andWhere($filter)
                ->setParameter('now', new DateTime());
        }

        $qb->join('a.creator', 'u');

        if (
            null !== $organs
            && null !== $user
        ) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('a.organ', ':organs'),
                    'u.lidnr = :user',
                ),
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
     * @param ?OrganModel[] $organs
     * @param int|null      $status An optional filter for activity status
     */
    public function getOldActivityPaginatorAdapterByOrganizer(
        ?array $organs = null,
        ?UserModel $user = null,
        ?int $status = null,
    ): DoctrineAdapter {
        $qb = $this->activityByOrganizerQuery(
            $this->getEntityManager()->createQueryBuilder()->expr()->lt('a.endTime', ':now'),
            $organs,
            $user,
            $status,
        );

        return new DoctrineAdapter(new ORMPaginator($qb));
    }

    /**
     * Returns the oldest activity that has taken place.
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
     * @return ActivityModel[]
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
            ->orderBy('a.beginTime', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all activities that were created by a member.
     *
     * @return ActivityModel[]
     */
    public function findAllActivitiesCreatedByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.creator = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all activities that were approved or rejected by a member.
     *
     * @return ActivityModel[]
     */
    public function findAllActivitiesApprovedByMember(MemberModel $member): array
    {
        $qb = $this->getRepository()->createQueryBuilder('a');
        $qb->where('a.approver = :member')
            ->setParameter('member', $member);

        return $qb->getQuery()->getResult();
    }

    #[Override]
    protected function getRepositoryName(): string
    {
        return ActivityModel::class;
    }
}
