<?php

declare(strict_types=1);

namespace App\Service\Activity;

use DateTime;

/**
 * The time windows that gate the sign-ups page. Centralised here so the friendly page-level gate
 * ({@see \App\Controller\Activity\AdminController::signups()}) and the security boundary
 * ({@see \App\Twig\Components\Activity\Admin\SignupOverview}) cannot drift apart.
 */
final class SignupAdminWindow
{
    /**
     * Organisers may view sign-up details until a week after the activity ends; the board is never time-limited.
     */
    public static function canView(
        DateTime $endTime,
        bool $isBoard,
    ): bool {
        return $isBoard
            || new DateTime() <= (clone $endTime)->modify('+1 week');
    }

    /**
     * Attendance may be marked from 30 minutes before the activity begins until a day after it ends.
     */
    public static function canMarkPresence(
        DateTime $beginTime,
        DateTime $endTime,
    ): bool {
        $now = new DateTime();

        return $now >= (clone $beginTime)->modify('-30 minutes')
            && $now <= (clone $endTime)->modify('+1 day');
    }

    /**
     * Admission (the draw and later manual admit/un-admit) may be changed until a day after the activity ends, the
     * same upper bound as attendance, so a draw forgotten before the activity can still be run at the door. Otherwise
     * a never-drawn limited list would strand: no admission and, since presence needs admission, no attendance either.
     */
    public static function canChangeAdmission(DateTime $endTime): bool
    {
        return new DateTime() <= (clone $endTime)->modify('+1 day');
    }
}
