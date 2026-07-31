<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

/**
 * Who a kind of notification is for, matching the three ways a notification can be addressed.
 *
 * It decides more than delivery: only what goes to everyone can be subscribed to by email, only what goes to an
 * account is worth telling a member is always on, and what goes to a role concerns whoever holds it rather than them.
 */
enum NotificationAddressing
{
    case Everyone;
    case Account;
    case Role;
}
