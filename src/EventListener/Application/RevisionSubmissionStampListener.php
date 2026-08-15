<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use DateTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Records the moment a revision reached the reviewers. Whoever applies the transition flushes afterwards, so the stamp
 * is persisted together with the new status.
 *
 * A draft can be written on Monday and submitted on Friday, so when it was written says nothing about how long the
 * board has had it: the queues are ordered and coloured by this moment instead.
 */
#[AsEventListener(event: 'workflow.revision.entered.submitted')]
final readonly class RevisionSubmissionStampListener
{
    /**
     * @param EnteredEvent<object> $event
     */
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof RevisionInterface) {
            return;
        }

        $revision->setSubmittedAt(new DateTime());
    }
}
