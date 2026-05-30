<?php

declare(strict_types=1);

namespace App\Repository\Activity;

use App\Entity\Activity\Activity;
use App\Entity\Activity\Enums\ActivityCategories;
use App\Entity\Activity\SignupList;
use App\Entity\Activity\UserSignup;
use App\Entity\Application\Enums\RevisionStatus;
use App\Entity\Decision\AssociationYear;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_keys;
use function array_map;
use function mb_strtolower;
use function rsort;
use function trim;

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
     * Activities for the admin overview whose working revision is NOT yet approved (drafts, submitted, in-review,
     * rejected, closed), most recently touched first. Scoped to the member unless $all (the board "show everything").
     *
     * @param int[] $organIds
     *
     * @return Activity[]
     */
    public function findPendingForAdmin(
        Member $member,
        array $organIds,
        bool $all,
    ): array {
        $qb = $this->adminOverviewQuery(
            $member,
            $organIds,
            $all,
        );
        $qb->andWhere('cr.status <> :approved')
            ->setParameter(
                'approved',
                RevisionStatus::Approved->value,
            )
            ->orderBy(
                'cr.updatedAt',
                'DESC',
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Approved activities for the admin overview, newest start first, paginated. Scoped to the member unless $all.
     *
     * @param int[] $organIds
     *
     * @return Paginator<Activity>
     */
    public function findApprovedForAdmin(
        Member $member,
        array $organIds,
        bool $all,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->adminOverviewQuery(
            $member,
            $organIds,
            $all,
        );
        $qb->andWhere('cr.status = :approved')
            ->setParameter(
                'approved',
                RevisionStatus::Approved->value,
            )
            ->orderBy(
                'cr.beginTime',
                'DESC',
            );

        $paginator = new Paginator(
            $qb,
            false,
        );
        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return $paginator;
    }

    /**
     * Base query for the admin overview: fetch-join the working revision (and its name text, organ, company and
     * author) so the row columns hydrate in one query, and scope to the member's own + their organs' activities
     * unless $all.
     *
     * @param int[] $organIds
     */
    private function adminOverviewQuery(
        Member $member,
        array $organIds,
        bool $all,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('a')
            ->addSelect(
                'cr',
                'n',
                'org',
                'comp',
                'au',
                'prev',
            )
            ->join(
                'a.currentRevision',
                'cr',
            )
            ->join(
                'cr.name',
                'n',
            )
            ->leftJoin(
                'a.organ',
                'org',
            )
            ->leftJoin(
                'a.company',
                'comp',
            )
            ->leftJoin(
                'cr.author',
                'au',
            )
            ->leftJoin(
                'cr.previousRevision',
                'prev',
            );

        if (!$all) {
            $qb->setParameter(
                'creatorLidnr',
                $member->getLidnr(),
            );

            if ([] === $organIds) {
                $qb->andWhere('IDENTITY(a.creator) = :creatorLidnr');
            } else {
                $qb->andWhere('(IDENTITY(a.creator) = :creatorLidnr OR IDENTITY(a.organ) IN (:organIds))')
                    ->setParameter(
                        'organIds',
                        $organIds,
                    );
            }
        }

        return $qb;
    }

    /**
     * The association years (first calendar year) that have at least one live (approved), past activity, newest first,
     * for the activity archive year switcher.
     *
     * @return int[]
     */
    public function getApprovedActivityYears(): array
    {
        /** @var list<array{beginTime: DateTime}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('lr.beginTime')
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->where('lr.endTime < :now')
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->getQuery()
            ->getResult();

        return $this->associationYears($rows);
    }

    /**
     * The association years (first calendar year) in which the given member has at least one sign-up for a live
     * (approved), past activity, newest first, for the "My past activities" year switcher.
     *
     * @return int[]
     */
    public function getSubscribedAssociationYears(Member $member): array
    {
        // A sign-up's list belongs to the live revision (sign-ups are migrated onto it on approval), so that
        // revision's schedule is the activity's live schedule.
        /** @var list<array{beginTime: DateTime}> $rows */
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->select('r.beginTime')
            ->from(
                UserSignup::class,
                'su',
            )
            ->join(
                'su.signupList',
                'sl',
            )
            ->join(
                'sl.revision',
                'r',
            )
            ->join(
                'r.activity',
                'a',
            )
            ->where('IDENTITY(su.user) = :subscriber')
            ->andWhere('r.endTime < :now')
            ->andWhere('IDENTITY(a.liveRevision) = r.id')
            ->setParameter(
                'subscriber',
                $member->getLidnr(),
                Types::INTEGER,
            )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            )
            ->getQuery()
            ->getResult();

        return $this->associationYears($rows);
    }

    /**
     * Maps a list of activity begin times to their distinct association years (first calendar year), newest first.
     *
     * @param list<array{beginTime: DateTime}> $rows
     *
     * @return int[]
     */
    private function associationYears(array $rows): array
    {
        $years = [];
        foreach ($rows as $row) {
            $years[AssociationYear::fromDate($row['beginTime'])->getYear()] = true;
        }

        $years = array_keys($years);
        rsort($years);

        return $years;
    }

    /**
     * Flexible query backing the activity overview pages (upcoming/archive, public/subscribed) with searching and
     * filtering. Only activities with a live (approved) revision are ever returned, and all content predicates read
     * from that live revision. Correlated EXISTS sub-queries keep the result at one row per activity, so the
     * Paginator counts correctly without a collection fetch-join.
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
        // Inner-join the live revision (so only approved activities surface) and fetch-join its to-one localised texts
        // so they are hydrated in this single query instead of one lazy SELECT per field per activity (N+1). These are
        // to-one, so they do not multiply rows and the paginator's LIMIT keeps working.
        $qb->addSelect(
            'lr',
            'n',
            'loc',
            'cost',
            'descr',
        )
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->join(
                'lr.name',
                'n',
            )
            ->join(
                'lr.location',
                'loc',
            )
            ->join(
                'lr.costs',
                'cost',
            )
            ->join(
                'lr.description',
                'descr',
            )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        if ($past) {
            $qb->andWhere('lr.endTime < :now')
                ->orderBy(
                    'lr.beginTime',
                    'DESC',
                );
        } else {
            $qb->andWhere('lr.endTime > :now')
                ->orderBy(
                    'lr.beginTime',
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
            $qb->andWhere('lr.category = :category')
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
                ->where('IDENTITY(sl_open.revision) = IDENTITY(a.liveRevision)')
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
                ->where('IDENTITY(sl_sub.revision) = IDENTITY(a.liveRevision)')
                ->andWhere('IDENTITY(su.user) = :subscriber');

            $qb->andWhere($qb->expr()->exists($subscriberSubquery->getDQL()))
                ->setParameter(
                    'subscriber',
                    $subscribedBy->getLidnr(),
                    Types::INTEGER,
                );
        }

        if (null !== $from) {
            $qb->andWhere('lr.beginTime >= :from')
                ->setParameter(
                    'from',
                    $from,
                    Types::DATETIME_MUTABLE,
                );
        }

        if (null !== $until) {
            $qb->andWhere('lr.beginTime <= :until')
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
     * Eager-loads the live revision's sign-up lists for the given activities in a single query, hydrating each
     * activity's (otherwise lazy) live-revision `signupLists` collection. This avoids the N+1 that the overview's
     * per-activity accessors ({@see Activity::getRelevantSignupList()}, {@see Activity::countPendingSignupLists()},
     * which read {@see Activity::getLiveSignupLists()}) would otherwise trigger.
     *
     * @param Activity[] $activities
     */
    public function primeSignupLists(array $activities): void
    {
        if ([] === $activities) {
            return;
        }

        $this->createQueryBuilder('a')
            ->select(
                'a',
                'lr',
                'sl',
            )
            ->leftJoin(
                'a.liveRevision',
                'lr',
            )
            ->leftJoin(
                'lr.signupLists',
                'sl',
            )
            ->where('a IN (:activities)')
            ->setParameter(
                'activities',
                $activities,
            )
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns the distinct organs that organise at least one live (approved) activity, for the overview's organ
     * filter.
     *
     * @return Organ[]
     */
    public function findOrganisingOrgans(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('DISTINCT IDENTITY(a.organ) AS organId')
            ->join(
                'a.liveRevision',
                'lr',
            )
            ->where('a.organ IS NOT NULL')
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
}
