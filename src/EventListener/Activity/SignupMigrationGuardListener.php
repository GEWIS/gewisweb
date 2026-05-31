<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use App\Service\Activity\SignupListMigrator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Withholds `approve`/`submit` for an activity revision whose approval could not carry the live sign-ups across.
 *
 * This only happens when a sign-up list was removed or restructured in this revision (only reachable by a race or
 * request tampering, since the form freezes such lists). This turns {@see MigrateSignupsOnApprovalListener}'s
 * last-resort hard-fail into a clean, up-front block: the action is not offered (and a forged request is refused).
 *
 * The review screen surfaces the reason and the recovery. A blocked draft cannot be fixed in place since its diverged
 * structure re-freezes read-only, so it MUST be discarded back to the live version
 * ({@see \App\Controller\Activity\AdminApprovalController::discard()}); a blocked in-review revision is rejected or
 * sent back for changes, which spawns a draft that is then discarded.
 *
 * Additive to {@see RevisionGuardListener}'s authorization guards on the same transitions; both must pass.
 */
final readonly class SignupMigrationGuardListener
{
    public function __construct(private SignupListMigrator $migrator)
    {
    }

    #[AsEventListener(event: 'workflow.revision.guard.approve')]
    public function onApprove(GuardEvent $event): void
    {
        $this->guard($event);
    }

    #[AsEventListener(event: 'workflow.revision.guard.submit')]
    public function onSubmit(GuardEvent $event): void
    {
        $this->guard($event);
    }

    private function guard(GuardEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        $live = $revision->getActivity()->getLiveRevision();
        if (
            null === $live
            || $live === $revision
            || $this->migrator->isMigratable(
                $live,
                $revision,
            )
        ) {
            return;
        }

        $event->setBlocked(
            true,
            'A sign-up list with sign-ups was changed in this revision; the live sign-ups cannot be carried over.',
        );
    }
}
