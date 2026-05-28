<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function array_key_exists;
use function array_merge;
use function count;
use function usort;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Activity::class,
        );
    }

    /**
     * Get upcoming activities sorted by date.
     *
     * @param int|null   $count optional number of activities to retrieve
     * @param Organ|null $organ option organ by whom the activities are organized
     *
     * @return Activity[]
     */
    public function getUpcomingActivities(
        ?int $count = null,
        ?Organ $organ = null,
        ?string $category = null,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->andWhere('a.status = :status')
            ->orderBy(
                'a.beginTime',
                'ASC',
            );

        if (null !== $count) {
            $qb->setMaxResults($count);
        }

        if (null !== $organ) {
            $qb->andWhere('a.organ = :organ')
                ->setParameter(
                    'organ',
                    $organ,
                    Organ::class,
                );
        }

        // For now 'career' is the only category, however this may change in the future
        if ('career' === $category) {
            $qb->andWhere('a.category = :category')
                ->setParameter(
                    'category',
                    ActivityCategories::Career->value,
                );
        }

        $qb->setParameter(
            'now',
            new DateTime(),
            Types::DATETIME_MUTABLE,
        );
        $qb->setParameter(
            'status',
            Activity::STATUS_APPROVED,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date for member.
     *
     * @param Member $member Option member that should relate to activity
     *
     * @return Activity[]
     */
    public function getUpcomingActivitiesForMember(Member $member): array
    {
        // Get subscriptions (not including non-approved)
        $result = $this->getUpcomingActivitiesSubscribedBy($member);

        // Get created by member (including non-approved)
        $result = array_merge(
            $result,
            $this->getUpcomingActivitiesCreatedBy($member),
        );

        // Get associated with organs (including non-approved)
        foreach ($member->getCurrentOrganInstallations() as $organMember) {
            $result = array_merge(
                $result,
                $this->getUpcomingActivitiesByOrgan($organMember->getOrgan()),
            );
        }

        // Do sorting based on start time
        usort(
            $result,
            static function ($a, $b) {
                $beginA = $a->getBeginTime();
                $beginB = $b->getBeginTime();

                return $beginA < $beginB
                    ? -1
                    : 1;
            },
        );

        $size = count($result);

        for ($i = 0; $i < $size; ++$i) {
            for ($j = $i + 1; $j < $size; ++$j) {
                if (
                    !array_key_exists(
                        $i,
                        $result,
                    )
                    || !array_key_exists(
                        $j,
                        $result,
                    )
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
     * Get upcoming activities sorted by date that a member is subscribed to.
     *
     * @param Member $member Option member that should relate to activity
     *
     * @return Activity[]
     */
    public function getUpcomingActivitiesSubscribedBy(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->from(
            SignupList::class,
            'b',
        )
            ->from(
                UserSignup::class,
                'c',
            )
            ->where('a.endTime > :now')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.status = :status')
            ->setParameter(
                'status',
                Activity::STATUS_APPROVED,
            )
            ->andWhere('a = b.activity')
            ->andWhere('b = c.signupList')
            ->andWhere('c.member = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date that a member created.
     *
     * @param Member $member Option member that should relate to activity
     *
     * @return Activity[]
     */
    public function getUpcomingActivitiesCreatedBy(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.creator = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get upcoming activities sorted by date that an organ created.
     *
     * @param Organ $organ Option organ that should relate to activity
     *
     * @return Activity[]
     */
    public function getUpcomingActivitiesByOrgan(Organ $organ): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.endTime > :now')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.organ = :organ')
            ->setParameter(
                'organ',
                $organ,
                Organ::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Gets upcoming activities of the given organs or member, sorted by date.
     *
     * @param ?Organ[] $organs
     * @param int|null $status An optional filter for activity status
     *
     * @return Activity[]
     */
    public function getAllUpcomingActivities(
        ?array $organs = null,
        ?Member $member = null,
        ?int $status = null,
    ): array {
        $qb = $this->activityByOrganizerQuery(
            $this->getEntityManager()->createQueryBuilder()->expr()->gt(
                'a.endTime',
                ':now',
            ),
            $organs,
            $member,
            $status,
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ?Organ[] $organs
     */
    protected function activityByOrganizerQuery(
        ?Comparison $filter = null,
        ?array $organs = null,
        ?Member $member = null,
        ?int $status = null,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('a');

        if (null !== $status) {
            $qb->where('a.status = :status')
                ->setParameter(
                    'status',
                    $status,
                );
        } else {
            $qb->where('a.status <> :status')
                ->setParameter(
                    'status',
                    Activity::STATUS_UPDATE,
                );
        }

        if (null !== $filter) {
            $qb->andWhere($filter)
                ->setParameter(
                    'now',
                    new DateTime(),
                    Types::DATETIME_MUTABLE,
                );
        }

        $qb->join(
            'a.creator',
            'u',
        );

        if (
            null !== $organs
            && null !== $member
        ) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in(
                        'a.organ',
                        ':organs',
                    ),
                    'u.lidnr = :member',
                ),
            )
                ->setParameter(
                    'organs',
                    $organs,
                )
                ->setParameter(
                    'member',
                    $member,
                    Member::class,
                );
        }

        $qb->orderBy(
            'a.beginTime',
            'DESC',
        );

        return $qb;
    }

    /**
     * Gets a paginator of old activities of the given organs, sorted by date.
     * Supplying 'null' to all arguments gets all activities.
     *
     * @param ?Organ[] $organs
     * @param int|null $status An optional filter for activity status
     *
     * @return Paginator<Activity>
     */
    public function getOldActivityPaginatorAdapterByOrganizer(
        ?array $organs = null,
        ?Member $member = null,
        ?int $status = null,
        int $page = 1,
        int $limit = 15,
    ): Paginator {
        $qb = $this->activityByOrganizerQuery(
            $this->getEntityManager()->createQueryBuilder()->expr()->lt(
                'a.endTime',
                ':now',
            ),
            $organs,
            $member,
            $status,
        );

        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult($limit * ($page - 1))
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * Returns the oldest activity that has taken place.
     */
    public function getOldestActivity(): ?Activity
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.status = :status')
            ->setParameter(
                'status',
                Activity::STATUS_APPROVED,
            )
            ->orderBy(
                'a.beginTime',
                'ASC',
            )
            ->setMaxResults(1);

        $res = $qb->getQuery()->getResult();

        return [] === $res
            ? null
            : $res[0];
    }

    /**
     * @return Activity[]
     */
    public function getArchivedActivitiesInRange(
        DateTime $start,
        DateTime $end,
    ): array {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.beginTime >= :start')
            ->setParameter(
                'start',
                $start,
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.endTime <= :end')
            ->setParameter(
                'end',
                $end,
                Types::DATETIME_MUTABLE,
            )
            ->andWhere('a.status = :status')
            ->setParameter(
                'status',
                Activity::STATUS_APPROVED,
            )
            ->orderBy(
                'a.beginTime',
                'DESC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all activities that were created by a member.
     *
     * @return Activity[]
     */
    public function findAllActivitiesCreatedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.creator = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all activities that were approved or rejected by a member.
     *
     * @return Activity[]
     */
    public function findAllActivitiesApprovedByMember(Member $member): array
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.approver = :member')
            ->setParameter(
                'member',
                $member,
                Member::class,
            );

        return $qb->getQuery()->getResult();
    }
}
