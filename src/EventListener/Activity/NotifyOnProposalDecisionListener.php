<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\NotificationType;
use App\Repository\User\UserRepository;
use App\Service\Application\NotificationPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\Event;

use function strval;

/**
 * Tells the body what the board decided about its proposal.
 *
 * A body that has asked for a day and hears nothing has no way of knowing whether to plan around it, which is how the
 * old calendar left everybody: the decision existed only as a colour on a page somebody had to remember to open.
 *
 * The member who handed it in is the one told. Notifications reach an account or a role, never a body, and that member
 * is the one who will be finishing the activity.
 */
final readonly class NotifyOnProposalDecisionListener
{
    public function __construct(
        private UserRepository $userRepository,
        private NotificationPublisher $publisher,
    ) {
    }

    /**
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.scheduled')]
    public function onScheduled(Event $event): void
    {
        // The board taking a clearance back also lands here, and that is not news about a decision: the body already
        // knows it has the day.
        if ('schedule' !== $event->getTransition()?->getName()) {
            return;
        }

        $this->tell(
            $event,
            NotificationType::ActivityProposalScheduled,
            AlertTypes::Success,
        );
    }

    /**
     * @param Event<object> $event
     */
    #[AsEventListener(event: 'workflow.activity_proposal.entered.declined')]
    public function onDeclined(Event $event): void
    {
        $this->tell(
            $event,
            NotificationType::ActivityProposalDeclined,
            AlertTypes::Info,
        );
    }

    /**
     * @param Event<object> $event
     */
    private function tell(
        Event $event,
        NotificationType $type,
        AlertTypes $level,
    ): void {
        $proposal = $event->getSubject();

        if (!$proposal instanceof ActivityProposal) {
            return;
        }

        $proposalId = $proposal->getId();

        if (null === $proposalId) {
            return;
        }

        $user = $this->userRepository->find($proposal->getCreatedBy()->getLidnr());

        if (null === $user) {
            return;
        }

        $this->publisher->publishFor(
            $user,
            $type,
            [
                'proposal' => strval($proposalId),
                'proposalName' => $proposal->getName(),
            ],
            $level,
        );
    }
}
