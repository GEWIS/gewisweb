<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\Application\Notification;
use App\Entity\User\NotificationInteraction;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<NotificationInteraction>
 */
class NotificationInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            NotificationInteraction::class,
        );
    }

    /**
     * What this member has done with each of these notifications, keyed by notification id. Loaded in one query so
     * showing the centre does not cost a lookup per row.
     *
     * @param Notification[] $notifications
     *
     * @return array<int, NotificationInteraction>
     */
    public function findForNotifications(
        User $user,
        array $notifications,
    ): array {
        if ([] === $notifications) {
            return [];
        }

        $ids = [];
        foreach ($notifications as $notification) {
            $id = $notification->getId();
            if (null === $id) {
                continue;
            }

            $ids[] = $id;
        }

        if ([] === $ids) {
            return [];
        }

        $interactions = $this->createQueryBuilder('i')
            ->where('i.user = :user')
            ->andWhere('i.notification IN (:notifications)')
            ->setParameter(
                'user',
                $user->getLidnr(),
            )
            ->setParameter(
                'notifications',
                $ids,
            )
            ->getQuery()
            ->getResult();

        $byNotification = [];
        foreach ($interactions as $interaction) {
            $id = $interaction->getNotification()->getId();
            if (null === $id) {
                continue;
            }

            $byNotification[$id] = $interaction;
        }

        return $byNotification;
    }

    /**
     * The member's record for this notification, created if they have not touched it before. Not flushed here; the
     * caller decides when.
     */
    public function getOrCreate(
        User $user,
        Notification $notification,
    ): NotificationInteraction {
        $interaction = $this->findOneBy([
            'user' => $user,
            'notification' => $notification,
        ]);

        if (null === $interaction) {
            $interaction = new NotificationInteraction(
                $user,
                $notification,
            );
            $this->getEntityManager()->persist($interaction);
        }

        return $interaction;
    }
}
