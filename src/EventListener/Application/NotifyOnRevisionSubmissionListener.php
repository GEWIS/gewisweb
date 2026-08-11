<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use App\Message\Application\PublishDomainNotificationMessage;
use App\Service\Application\RevisionNotificationRegistry;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Tells the people who review a domain that something of theirs is waiting. Which kind of notification that is, and who
 * it is addressed to, is the domain's own answer through {@see \App\Service\Application\RevisionNotificationInterface};
 * that a submission raises one at all is true everywhere, so it is said once here.
 */
#[AsEventListener(event: 'workflow.revision.entered.submitted')]
final readonly class NotifyOnRevisionSubmissionListener
{
    public function __construct(
        private RevisionNotificationRegistry $notifications,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @param EnteredEvent<object> $event
     */
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof RevisionInterface) {
            return;
        }

        $notification = $this->notifications->for($revision);
        if (null === $notification) {
            return;
        }

        $id = $revision->getId();
        if (null === $id) {
            return;
        }

        $this->messageBus->dispatch(new PublishDomainNotificationMessage(
            $notification->awaitingReviewType($revision),
            $id,
            $notification->audienceRole($revision),
        ));
    }
}
