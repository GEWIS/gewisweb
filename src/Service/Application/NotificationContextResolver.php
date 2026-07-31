<?php

declare(strict_types=1);

namespace App\Service\Application;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Enums\NotificationType;

/**
 * Turns the context a notification carries into the words its sentence is built around.
 *
 * The counterpart to {@see NotificationSubjectResolver}: that one looks a subject up, this one reads what the
 * notification froze at the time. Every kind that keeps a context says here how to read it, so adding one is a single
 * arm rather than a change everywhere a notification is rendered.
 */
final readonly class NotificationContextResolver
{
    public function __construct(
        private DeviceDescription $deviceDescription,
    ) {
    }

    /**
     * @param array<string, string> $context
     */
    public function resolve(
        NotificationType $type,
        array $context,
        Languages $language,
    ): ?string {
        return match ($type) {
            NotificationType::AlbumPublished, NotificationType::ActivityPublished => null,
            NotificationType::SignIn, NotificationType::PasswordChanged, NotificationType::MfaEnabled,
            NotificationType::MfaDisabled, NotificationType::BackupCodesRegenerated => $this->deviceDescription->render(
                $context['browser'] ?? null,
                $context['system'] ?? null,
                $context['address'] ?? null,
                $language,
            ),
        };
    }
}
