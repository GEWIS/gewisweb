<?php

declare(strict_types=1);

namespace App\Repository\Decision;

use App\Entity\Decision\Enums\MembershipTypes;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganMember;
use App\Entity\User\User;
use App\Entity\User\UserRole;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function ctype_digit;
use function strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Member::class,
        );
    }

    /**
     * Find a member by its membership number (NOTE: only members who are not deleted are returned).
     *
     * @param int $number Membership number
     */
    public function findByLidnr(int $number): ?Member
    {
        return $this->findOneBy([
            'lidnr' => $number,
            'deleted' => false,
        ]);
    }

    /**
     * Used for password resets to look up a member by email + lidnr; does not include members who are hidden, expired,
     * and/or deleted. These requirements are also used during the login process.
     *
     * Operates on Member (not User), so it also resolves Members synced from GEWISDB that do not yet have a User row.
     */
    public function findForReset(
        string $email,
        int $lidnr,
    ): ?Member {
        $qb = $this->createQueryBuilder('m')
            ->where('m.lidnr = :lidnr')
            ->andWhere('LOWER(m.email) = :email')
            ->andWhere('m.deleted = :false')
            ->andWhere('m.hidden = :false')
            ->andWhere('m.expiration > :now');

        $qb->setParameter(
            'lidnr',
            $lidnr,
        )
            ->setParameter(
                'email',
                strtolower($email),
            )
            ->setParameter(
                'false',
                false,
            )
            ->setParameter(
                'now',
                new DateTime('now'),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds members (lidnr, full name, and generation) by (part of) their name.
     *
     * @param string $name (part of) the full name of a member
     *
     * @return array<array-key, array{
     *     lidnr: int,
     *     fullName: string,
     *     generation: int,
     * }>
     */
    public function searchByName(
        string $name,
        int $maxResults = 32,
        string $orderColumn = 'generation',
        string $orderDirection = 'DESC',
    ): array {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult(
            'lidnr',
            'lidnr',
            'integer',
        )
            ->addScalarResult(
                'fullName',
                'fullName',
            )
            ->addScalarResult(
                'generation',
                'generation',
                'integer',
            );

        // This feeds the photo-tag autocomplete only ({@see \App\Controller\Decision\MemberController::search()}), so
        // members who opted out of being tagged are excluded here. LEFT JOIN + COALESCE because most members
        // have no UserSettings row; `lidnr` is qualified because both tables carry it.
        $sql = <<<QUERY
            SELECT `Member`.`lidnr` as `lidnr`,
                CONCAT_WS(' ', `firstName`, IF(LENGTH(`middleName`), `middleName`, NULL), `lastName`) as `fullName`,
                `generation`
            FROM `Member`
            LEFT JOIN `UserSettings` ON `UserSettings`.`lidnr` = `Member`.`lidnr`
            WHERE
                (
                CONCAT(LOWER(`firstName`), ' ', LOWER(`lastName`)) LIKE :name
                OR CONCAT(LOWER(`firstName`), ' ', LOWER(`middleName`), ' ', LOWER(`lastName`)) LIKE :name
                )
                AND deleted = 0
                AND expiration >= NOW()
                AND hidden = 0
                AND COALESCE(`UserSettings`.`photoTaggingOptOut`, 0) = 0
            ORDER BY $orderColumn $orderDirection LIMIT :limit
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery(
            $sql,
            $rsm,
        );
        $query->setParameter(
            ':name',
            '%' . strtolower($name) . '%',
        )
            ->setParameter(
                ':limit',
                $maxResults,
            );

        return $query->getArrayResult();
    }

    /**
     * Find all members with a birthday in the next $days days.
     *
     * When $days equals 0 or is not given, it will give all birthdays of today.
     * We do not show members whose membership has expired or who are hidden
     *
     * @param int $days the number of days to look ahead
     *
     * @return Member[] sorted by birthday
     */
    public function findBirthdayMembers(int $days): array
    {
        // unfortunately, there is no support for functions like DAY() and MONTH()
        // in doctrine2, thus we have to use the NativeSQL here
        $builder = new ResultSetMappingBuilder($this->getEntityManager());
        $builder->addRootEntityFromClassMetadata(
            $this->getEntityName(),
            'm',
        );

        $select = $builder->generateSelectClause(['m' => 't1']);

        // LEFT JOIN + COALESCE so members without a UserSettings row are kept; members who hid their birthday from the
        // home page are dropped entirely (distinct from hiding only their year of birth, which keeps them on
        // the panel but withholds the age - that is handled at render time via PrivacyService).
        $sql = <<<QUERY
            SELECT $select FROM Member AS t1
            LEFT JOIN UserSettings AS us ON us.lidnr = t1.lidnr
            WHERE DATEDIFF(
                DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR),
                DATE_SUB(CURDATE(), INTERVAL YEAR(CURDATE()) YEAR)
            ) BETWEEN 0 AND :days
            AND t1.deleted = 0
            AND t1.expiration > CURDATE()
            AND t1.hidden = 0
            AND COALESCE(us.hideBirthdayOnFrontpage, 0) = 0
            ORDER BY DATE_SUB(t1.birth, INTERVAL YEAR(t1.birth) YEAR) ASC
            QUERY;

        $query = $this->getEntityManager()->createNativeQuery(
            $sql,
            $builder,
        );
        $query->setParameter(
            'days',
            $days,
        );

        return $query->getResult();
    }

    /**
     * Find all organs of this member.
     *
     * @return Organ[]
     */
    public function findOrgans(Member $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('DISTINCT o')
            ->from(
                Organ::class,
                'o',
            )
            ->join(
                'o.members',
                'om',
            )
            ->join(
                'om.member',
                'm',
            )
            ->where('m.lidnr = :lidnr')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('om.dischargeDate'),
                $qb->expr()->gt(
                    'om.dischargeDate',
                    ':now',
                ),
            ));

        $qb->setParameter(
            'lidnr',
            $member->getLidnr(),
        )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all active installations of a member.
     *
     * @return OrganMember[]
     */
    public function findCurrentInstallations(Member $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('om')
            ->from(
                OrganMember::class,
                'om',
            )
            ->leftJoin(
                'om.organ',
                'o',
            )
            ->where('om.member = :member')
            ->andWhere('om.installDate <= :now')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('om.dischargeDate'),
                $qb->expr()->gt(
                    'om.dischargeDate',
                    ':now',
                ),
            ));

        $qb->setParameter(
            'member',
            $member->getLidnr(),
        )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all past installations of a member.
     *
     * @return OrganMember[]
     */
    public function findHistoricalInstallations(Member $member): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('om')
            ->from(
                OrganMember::class,
                'om',
            )
            ->leftJoin(
                'om.organ',
                'o',
            )
            ->where('om.member = :member')
            ->andWhere($qb->expr()->andX(
                $qb->expr()->isNotNull('om.dischargeDate'),
                $qb->expr()->lte(
                    'om.dischargeDate',
                    ':now',
                ),
            ));

        $qb->setParameter(
            'member',
            $member->getLidnr(),
        )
            ->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Admin overview paginator: every member, including hidden / deleted / expired ones, with their associated `User`
     * (if any) hydrated so the row knows whether the account is activated and whether MFA is enabled.
     *
     * @param array<string, mixed> $filters
     * @psalm-param array{
     *     type?: MembershipTypes|null,
     *     hiddenOnly?: bool,
     *     deletedOnly?: bool,
     *     expiredOnly?: bool,
     *     activatedOnly?: bool,
     *     mfaOnly?: bool,
     * } $filters
     *
     * @return Paginator<Member>
     */
    public function paginateForAdmin(
        string $search,
        string $sort,
        string $direction,
        array $filters,
        int $page,
        int $pageSize,
    ): Paginator {
        // The User row is joined for filter predicates (activatedOnly / mfaOnly) but intentionally NOT added to the
        // select. Adding it would flatten the result into a heterogeneous [Member, User, Member, User, ...] list
        // (see [[findAllWithUserDetails]] for the same effect). The component hydrates users separately by lidnr.
        $qb = $this->createQueryBuilder('m')
            ->leftJoin(
                User::class,
                'u',
                'WITH',
                'm.lidnr = u.lidnr',
            );

        $search = trim($search);
        if ('' !== $search) {
            $needle = '%' . strtolower($search) . '%';
            // DQL does not have CONCAT_WS, only CONCAT, so combined-field matches use nested CONCAT with explicit
            // spaces. The two combined forms cover the common "first last" and "first middle last" search shapes.
            $expr = $qb->expr()->orX(
                $qb->expr()->like(
                    'LOWER(m.firstName)',
                    ':needle',
                ),
                $qb->expr()->like(
                    'LOWER(m.middleName)',
                    ':needle',
                ),
                $qb->expr()->like(
                    'LOWER(m.lastName)',
                    ':needle',
                ),
                $qb->expr()->like(
                    "LOWER(CONCAT(m.firstName, ' ', m.lastName))",
                    ':needle',
                ),
                $qb->expr()->like(
                    "LOWER(CONCAT(m.firstName, ' ', CONCAT(m.middleName, CONCAT(' ', m.lastName))))",
                    ':needle',
                ),
            );

            if (ctype_digit($search)) {
                $expr->add(
                    $qb->expr()->like(
                        'm.lidnr',
                        ':needle',
                    ),
                );
            }

            $qb->andWhere($expr)->setParameter(
                'needle',
                $needle,
            );
        }

        $typeFilter = $filters['type'] ?? null;
        if ($typeFilter instanceof MembershipTypes) {
            $qb->andWhere('m.type = :type')->setParameter(
                'type',
                $typeFilter->value,
                Types::STRING,
            );
        }

        if (true === ($filters['hiddenOnly'] ?? false)) {
            $qb->andWhere('m.hidden = true');
        }

        if (true === ($filters['deletedOnly'] ?? false)) {
            $qb->andWhere('m.deleted = true');
        }

        if (true === ($filters['expiredOnly'] ?? false)) {
            $qb->andWhere('m.expiration < :now')->setParameter(
                'now',
                new DateTime(),
                Types::DATETIME_MUTABLE,
            );
        }

        if (true === ($filters['activatedOnly'] ?? false)) {
            $qb->andWhere('u.lidnr IS NOT NULL');
        }

        if (true === ($filters['mfaOnly'] ?? false)) {
            $qb->andWhere('u.totpSecret IS NOT NULL');
        }

        $orderField = match ($sort) {
            'name' => 'm.lastName',
            'type' => 'm.type',
            'expiration' => 'm.expiration',
            default => 'm.lidnr',
        };
        $qb->orderBy(
            $orderField,
            'desc' === strtolower($direction) ? 'DESC' : 'ASC',
        );

        $qb->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);

        return new Paginator($qb);
    }

    /**
     * Fetch all members including their associated user.
     *
     * NOTE: The ordering of the return array is not as you might expect. The actual result will be like:
     *
     * array{
     *     0: Member,
     *     1: ?UserModel,
     *     2: Member,
     *     3: ... (repeat pattern)
     * }
     *
     * In other words, every 2 rows represent a single `Member`.
     *
     * @return array<array-key, Member|User|UserRole|null>
     */
    public function findAllWithUserDetails(): array
    {
        $qb = $this->createQueryBuilder('m');
        // `u.settings` is fetch-joined because `User::$settings` is an eager inverse one-to-one; without it, hydrating
        // each user here would fire one extra query per user.
        $qb->leftJoin(
            User::class,
            'u',
            'WITH',
            'm.lidnr = u.lidnr',
        )
            ->addSelect('u')
            ->leftJoin(
                'u.settings',
                's',
            )
            ->addSelect('s');

        return $qb->getQuery()->getResult();
    }
}
