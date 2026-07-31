<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\Enums\NotificationType;
use App\Entity\User\Enums\UserRoles;
use App\Message\Application\PublishDomainNotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Tells the board an activity is waiting for them. Addressed to the role rather than to each member holding it,
 * because board membership is worked out from current installations rather than stored, and one row per submission
 * beats one per reviewer either way.
 */
#[AsEventListener(event: 'workflow.revision.entered.submitted')]
final readonly class NotifyOnActivitySubmissionListener
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

        $id = $revision->getId();
        if (null === $id) {
            return;
        }

        $this->messageBus->dispatch(new PublishDomainNotificationMessage(
            NotificationType::ActivityAwaitingReview,
            $id,
            UserRoles::Board,
        ));
    }
}
