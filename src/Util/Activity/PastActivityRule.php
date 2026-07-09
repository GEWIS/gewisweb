<?php

declare(strict_types=1);

namespace App\Util\Activity;

use App\Entity\Activity\ActivityRevision;
use DateTime;

/**
 * The single definition of "this activity can no longer be published or changed because it is in the past", shared by
 * the workflow guard ({@see \App\EventListener\Activity\PastActivityGuardListener}), the review screen and the admin
 * edit/reopen gate so the rule never diverges between the block and the explanation of it.
 *
 * Two cases, judged on different points of the schedule:
 *  - an *established* activity (it already has a live revision, distinct from the one in flight) is frozen once its
 *    live schedule has *ended*;
 *  - a *brand-new* activity awaiting its first publication (no live revision yet, or the in-flight revision is itself
 *    the live one) can never debut once its own *start* has passed, since its sign-up lists close before it begins.
 */
final class PastActivityRule
{
    /**
     * Whether the revision's scheduled end lies in the past (its end is the activity's real schedule once live).
     */
    public static function ended(ActivityRevision $revision): bool
    {
        $endTime = $revision->getEndTime();

        return null !== $endTime && $endTime < new DateTime();
    }

    /**
     * Whether the revision's scheduled start lies in the past.
     */
    public static function started(ActivityRevision $revision): bool
    {
        $beginTime = $revision->getBeginTime();

        return null !== $beginTime && $beginTime < new DateTime();
    }

    /**
     * The established-activity case: there is a live revision, the one in flight is a re-edit of it (not the live one),
     * and that live schedule has ended.
     */
    public static function liveEnded(
        ?ActivityRevision $live,
        ActivityRevision $revision,
    ): bool {
        return null !== $live
            && $live !== $revision
            && self::ended($live);
    }

    /**
     * The brand-new-activity case: no live revision yet (or the in-flight revision is itself the live one) and its own
     * start has already passed.
     */
    public static function debutMissed(
        ?ActivityRevision $live,
        ActivityRevision $revision,
    ): bool {
        return (null === $live || $live === $revision)
            && self::started($revision);
    }
}
