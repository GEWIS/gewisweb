<?php

declare(strict_types=1);

namespace App\EventListener\Career;

use App\Entity\Application\Enums\NotificationType;
use App\Entity\Career\CompanyRevision;
use App\Entity\Career\VacancyRevision;
use App\Entity\User\Enums\UserRoles;
use App\Message\Application\PublishDomainNotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Tells C4 that a company has put something forward. Addressed to the role rather than to
 * each member holding it, so one submission is one row however many people are on the committee, and the board sees it
 * too since its own role reaches through.
 */
#[AsEventListener(event: 'workflow.revision.entered.submitted')]
final readonly class NotifyOnCareerSubmissionListener
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

        $type = match (true) {
            $revision instanceof CompanyRevision => NotificationType::CompanyRevisionAwaitingReview,
            $revision instanceof VacancyRevision => NotificationType::VacancyRevisionAwaitingReview,
            default => null,
        };

        if (null === $type) {
            return;
        }

        $id = $revision->getId();
        if (null === $id) {
            return;
        }

        $this->messageBus->dispatch(new PublishDomainNotificationMessage(
            $type,
            $id,
            UserRoles::CompanyAdmin,
        ));
    }
}
