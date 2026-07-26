<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\NotificationType;
use App\Message\Application\PublishDomainNotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Announces an activity the first time it goes live. Runs before {@see PromoteLiveRevisionListener} repoints the live
 * revision, so a null live revision here means the activity has never been public; a later revision (an edit) already
 * has one and is skipped. Dispatched rather than published inline so a hub hiccup cannot fail the approval.
 */
#[AsEventListener(
    event: 'workflow.revision.entered.approved',
    priority: 5,
)]
final readonly class NotifyOnActivityApprovalListener
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    /**
     * @param EnteredEvent<object> $event
     */
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $activity = $revision->getActivity();
        if (null !== $activity->getLiveRevision()) {
            return;
        }

        $id = $activity->getId();
        if (null === $id) {
            return;
        }

        $this->messageBus->dispatch(new PublishDomainNotificationMessage(
            NotificationType::ActivityPublished,
            $id,
        ));
    }
}
