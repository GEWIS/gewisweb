<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Service\Activity\SignupListMigrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * When an activity revision is approved it becomes the publicly live version. Because each revision owns its own
 * (cloned) sign-up lists, the existing sign-ups (which live on the outgoing live revision's lists) are migrated onto
 * the newly-approved revision's matching lists (matched by lineage id) before that revision is promoted, so the public
 * page keeps showing them and no sign-up is ever lost.
 *
 * Runs at a higher priority than {@see PromoteLiveRevisionListener}: the migration must read the still-current live
 * revision (`getLiveRevision()`) before that generic listener repoints it to the newly-approved one. Promotion itself
 * is left to {@see PromoteLiveRevisionListener} (shared by every domain), so this listener only migrates.
 *
 * Runs in-memory only; the controller flushes after `$workflow->apply()`. The migrator hard-fails on an incompatible
 * revision, but {@see SignupMigrationGuardListener} withholds the `approve` transition up front.
 */
#[AsEventListener(
    event: 'workflow.revision.entered.approved',
    priority: 10,
)]
final readonly class MigrateSignupsOnApprovalListener
{
    public function __construct(private SignupListMigrator $migrator)
    {
    }

    public function __invoke(EnteredEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $activity = $revision->getActivity();
        $outgoing = $activity->getLiveRevision();

        if (
            null === $outgoing
            || $outgoing === $revision
        ) {
            return;
        }

        $this->migrator->migrate(
            $outgoing,
            $revision,
        );
    }
}
