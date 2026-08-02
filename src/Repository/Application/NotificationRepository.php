<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Application\Notification;
use App\Entity\User\Enums\UserRoles;
use App\Entity\User\NotificationInteraction;
use App\Entity\User\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Notification::class,
        );
    }

    /**
     * The most recent notifications within the window that this user may see, newest first: everything addressed to
     * nobody in particular, everything addressed to them, and everything addressed to a role they hold.
     *
     * Anything the viewer has cleared away is excluded here rather than afterwards, so the limit counts what they will
     * actually be shown: clearing ten notifications must not leave them with an empty centre while older ones are
     * still within the window.
     *
     * Deliberately not result-cached. One list used to serve every member, which made caching worth it; now that it is
     * per-member the saving is small, and a cached list would keep being handed back after the notification centre has
     * already been told to refresh itself.
     *
     * @param UserRoles[] $roles every role the viewer holds, hierarchy included
     *
     * @return Notification[]
     */
    public function findRecentFor(
        DateTimeImmutable $since,
        User $viewer,
        array $roles,
        int $limit,
    ): array {
        return $this->createQueryBuilder('n')
            ->where('n.createdAt > :since')
            ->andWhere(
                'n.recipientUser = :viewer'
                . ' OR n.recipientRole IN (:roles)'
                . ' OR (n.recipientUser IS NULL AND n.recipientCompanyUser IS NULL AND n.recipientRole IS NULL)',
            )
            ->andWhere(
                'NOT EXISTS ('
                . 'SELECT 1 FROM ' . NotificationInteraction::class . ' i '
                . 'WHERE i.notification = n AND i.user = :viewer AND i.dismissedAt IS NOT NULL'
                . ')',
            )
            ->setParameter(
                'since',
                $since,
                Types::DATETIME_IMMUTABLE,
            )
            ->setParameter(
                'viewer',
                $viewer->getLidnr(),
            )
            ->setParameter(
                'roles',
                $roles,
            )
            ->orderBy(
                'n.createdAt',
                'DESC',
            )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Take down the notification about a subject that has since been dealt with. Announcing is deduplicated per
     * subject and type, so one that outlives what it was about would keep the next one from being published at all.
     */
    public function removeForSubject(
        NotificationType $type,
        int $subjectId,
    ): void {
        $this->createQueryBuilder('n')
            ->delete()
            ->where('n.type = :type')
            ->andWhere('n.subjectId = :subjectId')
            ->setParameter(
                'type',
                $type->value,
                Types::STRING,
            )
            ->setParameter(
                'subjectId',
                $subjectId,
                Types::INTEGER,
            )
            ->getQuery()
            ->execute();
    }
}
