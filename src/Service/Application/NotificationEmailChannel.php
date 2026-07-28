<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Notification;
use App\Entity\User\PendingNotificationEmail;
use App\Repository\User\NotificationEmailSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Override;

/**
 * The email channel: queues the notification for the members who opted into email for its category. Queuing (rather
 * than mailing here) lets the digest job batch a member's notifications and mail them at their chosen frequency, which
 * keeps a busy day from becoming a flood of separate emails.
 */
final readonly class NotificationEmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private NotificationEmailSubscriptionRepository $subscriptions,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Override]
    public function deliver(Notification $notification): void
    {
        $queued = false;
        foreach ($this->subscriptions->findSubscribedUsers($notification->getType()) as $user) {
            $member = $user->getMember();
            if (
                null === $member->getEmail()
                || $member->getDeleted()
                || $member->getHidden()
                || $member->isExpired()
            ) {
                continue;
            }

            $this->entityManager->persist(new PendingNotificationEmail(
                $user,
                $notification,
            ));
            $queued = true;
        }

        if (!$queued) {
            return;
        }

        $this->entityManager->flush();
    }
}
