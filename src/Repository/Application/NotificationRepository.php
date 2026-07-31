<?php

declare(strict_types=1);

namespace App\Repository\Application;

use App\Entity\Application\Notification;
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
     * nobody in particular, plus everything addressed to them.
     *
     * Anything the viewer has cleared away is excluded here rather than afterwards, so the limit counts what they will
     * actually be shown: clearing ten notifications must not leave them with an empty centre while older ones are
     * still within the window.
     *
     * Deliberately not result-cached. One list used to serve every member, which made caching worth it; now that it is
     * per-member the saving is small, and a cached list would keep being handed back after the notification centre has
     * already been told to refresh itself.
     *
     * @return Notification[]
     */
    public function findRecentFor(
        DateTimeImmutable $since,
        User $viewer,
        int $limit,
    ): array {
        return $this->createQueryBuilder('n')
            ->where('n.createdAt > :since')
            ->andWhere('n.recipientUser = :viewer OR (n.recipientUser IS NULL AND n.recipientCompanyUser IS NULL)')
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
            ->orderBy(
                'n.createdAt',
                'DESC',
            )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
