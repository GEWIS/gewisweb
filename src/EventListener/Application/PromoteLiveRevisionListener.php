<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use App\Entity\Activity\ActivityRevision;
use App\Entity\Application\RevisionInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

use function assert;

/**
 * When a revision is approved it becomes the publicly live version of its aggregate. The just-approved revision is
 * always the newest in the chain, so it supersedes any previously live revision (which stays as an immutable record).
 *
 * Activity revisions are promoted by {@see MigrateSignupsOnApprovalListener} instead, which must first move the live
 * sign-ups onto the newly-approved revision's lists (capturing the outgoing live revision before promoting), so they
 * are skipped here. Other domains (companies, vacancies) have no sign-up graph and are promoted directly.
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

        if ($revision instanceof ActivityRevision) {
            return;
        }

        $revision->getRevisable()->markRevisionLive($revision);
    }
}
