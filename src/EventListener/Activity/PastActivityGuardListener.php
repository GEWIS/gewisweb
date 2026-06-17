<?php

declare(strict_types=1);

namespace App\EventListener\Activity;

use App\Entity\Activity\ActivityRevision;
use DateTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Withholds `submit`/`approve` when an activity can no longer be published or changed:
 *
 *  - an *established* activity (it already has a live revision) is immutable once that live revision has *ended*:
 *    no new content may be staged onto it or promoted live (the end itself stays editable while it runs, in the
 *    form). Recovery: reject/close the in-flight revision, or discard it back to the live version.
 *  - a *brand-new* activity awaiting its first publication (no live revision yet) is blocked once its own *start*
 *    has passed: one that has already started can never debut, since its sign-up lists close before it begins.
 *    Recovery: its start stays editable ({@see \App\Form\Activity\ActivityRevisionType}), so the organiser
 *    re-dates the draft into the future and resubmits.
 *
 * The controller already refuses to open a passed live activity for editing
 * ({@see \App\Controller\Activity\AdminController::edit()}); this closes the loop for a revision still in flight
 * when its deadline passed. Activity-scoped, additive to the authorization guards in
 * {@see \App\EventListener\Application\RevisionGuardListener} and {@see SignupMigrationGuardListener}; all must pass.
 */
final readonly class PastActivityGuardListener
{
    #[AsEventListener(event: 'workflow.revision.guard.submit')]
    public function onSubmit(GuardEvent $event): void
    {
        $this->guard($event);
    }

    #[AsEventListener(event: 'workflow.revision.guard.approve')]
    public function onApprove(GuardEvent $event): void
    {
        $this->guard($event);
    }

    private function guard(GuardEvent $event): void
    {
        $revision = $event->getSubject();
        if (!$revision instanceof ActivityRevision) {
            return;
        }

        // An established activity (it already has a live revision) is judged by its live schedule's *end* -- the end
        // stays editable while it runs, so its content is frozen only once it has ended. A brand-new activity
        // awaiting its first publication is judged by its own *start*: one that has already started can never debut,
        // since its sign-up lists close before it begins, so it could never be joined.
        $live = $revision->getActivity()->getLiveRevision();
        if (
            null !== $live
            && $live !== $revision
        ) {
            $deadline = $live->getEndTime();
            $message = 'This activity has already taken place; its content can no longer be changed.';
        } else {
            $deadline = $revision->getBeginTime();
            $message = 'This activity has already started, so it can no longer be published.';
        }

        if (
            null === $deadline
            || $deadline >= new DateTime()
        ) {
            return;
        }

        $event->setBlocked(
            true,
            $message,
        );
    }
}
