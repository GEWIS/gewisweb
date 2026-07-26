<?php

declare(strict_types=1);

namespace App\MessageHandler\Application;

use App\Entity\Application\Notification;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Repository\Application\NotificationRepository;
use App\Service\Application\NotificationPublisher;
use App\Service\Application\NotificationSubjectResolver;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PublishDomainNotificationHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationPublisher $notificationPublisher,
        private readonly NotificationSubjectResolver $subjectResolver,
    ) {
    }

    public function __invoke(PublishDomainNotificationMessage $message): void
    {
        $type = $message->getType();
        $subjectId = $message->getSubjectId();

        // The subject can be gone by the time this runs, since it is announced asynchronously.
        if (
            null === $this->subjectResolver->nameFor(
                $type,
                $subjectId,
            )
        ) {
            return;
        }

        // Announcing is idempotent: a subject can become public more than once (an album taken offline and put back),
        // and a worker that dies after the row is written retries the same message. The unique constraint on the
        // table is what guarantees this; the check here keeps the ordinary case from having to catch a violation.
        $existing = $this->notificationRepository->findOneBy([
            'type' => $type,
            'subjectId' => $subjectId,
        ]);

        if (null !== $existing) {
            return;
        }

        $notification = new Notification();
        $notification->setType($type);
        $notification->setSubjectId($subjectId);

        $this->notificationPublisher->publish($notification);
    }
}
