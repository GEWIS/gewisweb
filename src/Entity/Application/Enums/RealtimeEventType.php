<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

/**
 * The `type` discriminator on every real-time envelope; the frontend `notifications` controller switches on it.
 */
enum RealtimeEventType: string
{
    case SessionInvalidate = 'session.invalidate';
    case ForceReload = 'force_reload';
    case Toast = 'toast';
    case InfimumRotate = 'infimum.rotate';
}
