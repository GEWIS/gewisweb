<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Notification;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

/**
 * Persists a {@see Notification} and fans it out to every delivery channel. The persisted row is the source of truth
 * (so a member who was offline still sees it in the notification centre); each channel then delivers it its own way.
 * Channels are best-effort: a channel that fails is logged and skipped so the others still run and a retry cannot
 * duplicate the already-persisted notification.
 */
final class NotificationPublisher
{
    /**
     * @param iterable<NotificationChannelInterface> $channels
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        #[AutowireIterator('app.notification_channel')]
        private readonly iterable $channels,
    ) {
    }

    public function publish(Notification $notification): void
    {
        $notification->setCreatedAt(new DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        foreach ($this->channels as $channel) {
            try {
                $channel->deliver($notification);
            } catch (Throwable $e) {
                $this->logger->warning(
                    'A notification channel failed to deliver.',
                    [
                        'channel' => $channel::class,
                        'exception' => $e,
                    ],
                );
            }
        }
    }
}
