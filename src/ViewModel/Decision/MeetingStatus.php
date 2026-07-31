<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use DateTime;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * The presentation state of a meeting: it has not happened yet, it happened but the outcome is still being processed,
 * or the outcome (decisions or minutes) has been published.
 */
enum MeetingStatus
{
    case Upcoming;
    case HeldProcessing;
    case Complete;

    public static function derive(
        DateTime $meetingDate,
        bool $hasDecisions,
        bool $hasMinutes,
    ): self {
        if ($meetingDate >= new DateTime('today')) {
            return self::Upcoming;
        }

        if (
            $hasDecisions
            || $hasMinutes
        ) {
            return self::Complete;
        }

        return self::HeldProcessing;
    }

    public function label(): TranslatableMessage
    {
        return match ($this) {
            self::Upcoming => new TranslatableMessage('Upcoming'),
            self::HeldProcessing => new TranslatableMessage('Held, being processed'),
            self::Complete => new TranslatableMessage('Held'),
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Upcoming => 'text-bg-primary',
            self::HeldProcessing => 'text-bg-warning',
            self::Complete => 'text-bg-light',
        };
    }
}
