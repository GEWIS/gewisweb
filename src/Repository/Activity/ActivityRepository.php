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

use function addcslashes;
use function array_key_exists;
use function array_map;
use function array_merge;
use function count;
use function mb_strtolower;
use function trim;
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
     * Flexible query backing the activity overview pages (upcoming/archive, public/subscribed) with searching and
     * filtering. Only approved activities are ever returned. Correlated EXISTS sub-queries keep the result at one row
     * per activity, so the Paginator counts correctly without a collection fetch-join.
     *
     * @param bool        $past         false: upcoming (endTime > now, ASC); true: past (endTime < now, DESC)
     * @param Member|null $subscribedBy when set, only activities this member has a (user) signup for
     * @param string      $locale       'nl' searches the Dutch name, anything else the English name
     * @param int[]       $labelIds     match activities having ANY of these labels
     * @param int|null    $organId      identifier of the organising organ to filter on
     *
     * @return Paginator<Activity>
     */
    public function findForOverview(
        bool $past,
        ?Member $subscribedBy,
        string $search,
        string $locale,
        ?ActivityCategories $category,
        array $labelIds,
        ?int $organId,
        bool $openSignupOnly,
        ?DateTime $from,
        ?DateTime $until,
        int $limit,
        int $offset,
    ): Paginator {
        $qb = $this->createQueryBuilder('a');
        // Fetch-join the to-one localised texts so they are hydrated in this single query instead of one lazy
        // `SELECT ... FROM ActivityLocalisedText WHERE id = ?` per field per activity (N+1). These are to-one, so they
        // do not multiply rows and the paginator's LIMIT keeps working.
        $qb->addSelect(
            'n',
            'loc',
            'cost',
            'descr',
        )
            ->join(
                'a.name',
                'n',
            )
            ->join(
                'a.location',
                'loc',
            )
            ->join(
                'a.costs',
                'cost',
            )
            ->join(
                'a.description',
                'descr',
            )
            ->where('a.status = :status')
            ->setParameter(
                'status',
                Activity::STATUS_APPROVED,
            )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        if ($past) {
            $qb->andWhere('a.endTime < :now')
                ->orderBy(
                    'a.beginTime',
                    'DESC',
                );
        } else {
            $qb->andWhere('a.endTime > :now')
                ->orderBy(
                    'a.beginTime',
                    'ASC',
                );
        }

        $search = trim($search);
        if ('' !== $search) {
            $column = 'nl' === $locale
                ? 'n.valueNL'
                : 'n.valueEN';
            // Escape the LIKE wildcards (`%` and `_`) in user input so they are matched literally instead of acting as
            // wildcards (see DecisionRepository::search). $column is a fixed internal field name, never user input.
            $qb->andWhere('LOWER(' . $column . ') LIKE :needle')
                ->setParameter(
                    'needle',
                    '%' . addcslashes(
                        mb_strtolower($search),
                        '%_',
                    ) . '%',
                );
        }

        if (null !== $category) {
            $qb->andWhere('a.category = :category')
                ->setParameter(
                    'category',
                    $category->value,
                );
        }

        if (null !== $organId) {
            $qb->andWhere('IDENTITY(a.organ) = :organId')
                ->setParameter(
                    'organId',
                    $organId,
                );
        }

        $entityManager = $this->getEntityManager();

        if ([] !== $labelIds) {
            $labelSubquery = $entityManager->createQueryBuilder()
                ->select('1')
                ->from(
                    Activity::class,
                    'a_lbl',
                )
                ->join(
                    'a_lbl.labels',
                    'lbl',
                )
                ->where('a_lbl = a')
                ->andWhere('lbl.id IN (:labelIds)');

            $qb->andWhere($qb->expr()->exists($labelSubquery->getDQL()))
                ->setParameter(
                    'labelIds',
                    $labelIds,
                );
        }

        if ($openSignupOnly) {
            $openSignupSubquery = $entityManager->createQueryBuilder()
                ->select('1')
                ->from(
                    SignupList::class,
                    'sl_open',
                )
                ->where('sl_open.activity = a')
                ->andWhere('sl_open.openDate <= :now')
                ->andWhere('sl_open.closeDate > :now');

            $qb->andWhere($qb->expr()->exists($openSignupSubquery->getDQL()));
        }

        if (null !== $subscribedBy) {
            $subscriberSubquery = $entityManager->createQueryBuilder()
                ->select('1')
                ->from(
                    UserSignup::class,
                    'su',
                )
                ->join(
                    'su.signupList',
                    'sl_sub',
                )
                ->where('sl_sub.activity = a')
                ->andWhere('su.user = :subscriber');

            $qb->andWhere($qb->expr()->exists($subscriberSubquery->getDQL()))
                ->setParameter(
                    'subscriber',
                    $subscribedBy,
                    Member::class,
                );
        }

        if (null !== $from) {
            $qb->andWhere('a.beginTime >= :from')
                ->setParameter(
                    'from',
                    $from,
                    Types::DATETIME_MUTABLE,
                );
        }

        if (null !== $until) {
            $qb->andWhere('a.beginTime <= :until')
                ->setParameter(
                    'until',
                    $until,
                    Types::DATETIME_MUTABLE,
                );
        }

        $paginator = new Paginator(
            $qb,
            false,
        );
        $paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $paginator;
    }

    /**
     * Returns the distinct organs that organise at least one approved activity, for the overview's organ filter.
     *
     * @return Organ[]
     */
    public function findOrganisingOrgans(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT IDENTITY(a.organ) AS organId')
            ->where('a.status = :status')
            ->setParameter(
                'status',
                Activity::STATUS_APPROVED,
            )
            ->andWhere('a.organ IS NOT NULL')
            ->getQuery()
            ->getScalarResult();

        $organIds = array_map(
            static fn (array $row): int => (int) $row['organId'],
            $rows,
        );

        if ([] === $organIds) {
            return [];
        }

        return $this->getEntityManager()->getRepository(Organ::class)->findBy(
            ['id' => $organIds],
            ['abbr' => 'ASC'],
        );
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
