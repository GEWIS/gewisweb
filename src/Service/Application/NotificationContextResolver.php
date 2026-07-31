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
 *
 * A kind whose sentence needs nothing filling in still belongs here, returning an empty string: it has no subject to
 * look up either, and null would mean there is nothing to show at all.
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
            // Nothing to fill in: the sentence says all of it on its own.
            NotificationType::DataExportReady => '',
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
