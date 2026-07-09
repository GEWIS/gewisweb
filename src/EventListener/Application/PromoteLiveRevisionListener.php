<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Application\RevisionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

use function assert;

/**
 * When a revision is approved it becomes the publicly live version of its aggregate. The just-approved revision is
 * always the newest in the chain, so it supersedes any previously live revision (which stays as an immutable record).
 *
 * This is the single promoter for every domain (activities, companies, vacancies). Activities additionally need their
 * live sign-ups migrated onto the newly-approved revision first; {@see MigrateSignupsOnApprovalListener} does that at a
 * higher priority (it must read the still-current live revision before this listener repoints it), then this listener
 * promotes, so no domain is special-cased here.
 *
 * Runs in-memory only; the controller flushes after `$workflow->apply()`.
 */
#[AsEventListener(event: 'workflow.revision.entered.approved')]
final readonly class PromoteLiveRevisionListener
{
    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        assert($revision instanceof RevisionInterface);

        $revision->getRevisable()->markRevisionLive($revision);
    }
}
