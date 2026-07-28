<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Notification;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A delivery channel for a published notification. The persisted notification is the source of truth; each channel
 * decides how, and to whom, it delivers. The website channel reaches everyone in real time and cannot be turned off;
 * other channels (e.g. email) deliver only to the members who opted in for the notification's category.
 *
 * Concrete channels are autoconfigured under the `app.notification_channel` tag and iterated by
 * {@see NotificationPublisher}.
 */
#[AutoconfigureTag('app.notification_channel')]
interface NotificationChannelInterface
{
    public function deliver(Notification $notification): void;
}
