<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use App\Security\User\Firewall;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * The kind of event a persisted {@see \App\Entity\Application\Notification} records. Drives the icon shown in the
 * notification centre and, as a category, the per-member email opt-in. It also holds everything the notification says:
 * the sentence, where it points and what the link reads, so a notification row only has to record its subject.
 */
enum NotificationType: string
{
    case AlbumPublished = 'album_published';
    case ActivityPublished = 'activity_published';
    case SignIn = 'sign_in';
    case PasswordChanged = 'password_changed';
    case MfaEnabled = 'mfa_enabled';
    case MfaDisabled = 'mfa_disabled';
    case BackupCodesRegenerated = 'backup_codes_regenerated';
    case DataExportReady = 'data_export_ready';
    case ActivityAwaitingReview = 'activity_awaiting_review';

    public function icon(): string
    {
        return match ($this) {
            self::AlbumPublished => 'fa-images',
            self::ActivityPublished => 'fa-calendar-day',
            self::SignIn => 'fa-right-to-bracket',
            self::PasswordChanged => 'fa-key',
            self::MfaEnabled => 'fa-lock',
            self::MfaDisabled => 'fa-unlock',
            self::BackupCodesRegenerated => 'fa-rotate',
            self::DataExportReady => 'fa-file-arrow-down',
            self::ActivityAwaitingReview => 'fa-clipboard-check',
        };
    }

    /**
     * The topic a member sees this under. Several kinds share one where they are one thing to whoever reads them.
     */
    public function category(): NotificationCategory
    {
        return match ($this) {
            self::AlbumPublished => NotificationCategory::Albums,
            self::ActivityPublished => NotificationCategory::Activities,
            self::SignIn => NotificationCategory::SignIns,
            self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated => NotificationCategory::AccountSecurity,
            self::DataExportReady => NotificationCategory::DataExports,
            self::ActivityAwaitingReview => NotificationCategory::ActivityReviews,
        };
    }

    public function addressing(): NotificationAddressing
    {
        return match ($this) {
            self::AlbumPublished, self::ActivityPublished => NotificationAddressing::Everyone,
            self::SignIn, self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated,
            self::DataExportReady => NotificationAddressing::Account,
            self::ActivityAwaitingReview => NotificationAddressing::Role,
        };
    }

    /**
     * The route a notification of this kind points at. Callers build the URL themselves, so the notification centre
     * links within the language being read instead of a language frozen when the notification was created.
     *
     * A kind that points at a page belonging to the recipient needs to know which firewall they are on, since the two
     * have separate routes for it.
     */
    public function route(?Firewall $recipient = null): string
    {
        return match ($this) {
            self::AlbumPublished => 'photo/album',
            self::ActivityPublished => 'activity/view',
            self::SignIn, self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated => ($recipient ?? Firewall::Main)->securityIndexRoute(),
            self::DataExportReady => 'user_settings_data_export_download',
            self::ActivityAwaitingReview => 'admin/activities/approvals/review',
        };
    }

    /**
     * @return array<string, int|string>
     */
    public function routeParameters(?int $subjectId): array
    {
        // A notification that stands on its own points at a page that needs no parameters.
        if (null === $subjectId) {
            return [];
        }

        return match ($this) {
            self::AlbumPublished => [
                'type' => 'album',
                'album' => $subjectId,
            ],
            self::ActivityPublished => ['activity' => $subjectId],
            self::ActivityAwaitingReview => ['revision' => $subjectId],
            self::SignIn, self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated, self::DataExportReady => [],
        };
    }

    /**
     * What the link to the subject reads.
     */
    public function linkLabel(): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage('View album'),
            self::ActivityPublished => new TranslatableMessage('View activity'),
            self::SignIn => new TranslatableMessage('Review your sessions'),
            self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated => new TranslatableMessage(
                'Review your account security',
            ),
            self::DataExportReady => new TranslatableMessage('Download your data'),
            self::ActivityAwaitingReview => new TranslatableMessage('Review it'),
        };
    }

    /**
     * The notification itself, with the subject's name filled in.
     */
    public function message(string $name): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage(
                'A new photo album "%name%" is online.',
                ['%name%' => $name],
            ),
            self::ActivityPublished => new TranslatableMessage(
                'A new activity "%name%" has been published.',
                ['%name%' => $name],
            ),
            self::SignIn => new TranslatableMessage(
                'Your account was signed in from %name%.',
                ['%name%' => $name],
            ),
            self::PasswordChanged => new TranslatableMessage(
                'Your password was changed from %name%.',
                ['%name%' => $name],
            ),
            self::MfaEnabled => new TranslatableMessage(
                'Two-factor authentication was enabled on your account from %name%.',
                ['%name%' => $name],
            ),
            self::MfaDisabled => new TranslatableMessage(
                'Two-factor authentication was disabled on your account from %name%.',
                ['%name%' => $name],
            ),
            self::BackupCodesRegenerated => new TranslatableMessage(
                'New backup codes were generated for your account from %name%.',
                ['%name%' => $name],
            ),
            self::DataExportReady => new TranslatableMessage('The data export you asked for is ready.'),
            self::ActivityAwaitingReview => new TranslatableMessage(
                'The activity "%name%" has been submitted for review.',
                ['%name%' => $name],
            ),
        };
    }

    /**
     * The subject line when a security notice of this kind is emailed, or null for a kind that is not emailed from
     * there: one that goes out in a digest, or one whose own handler already writes to the member.
     *
     * Plain English rather than a translatable message, because outgoing mail is always English.
     */
    public function emailSubject(): ?string
    {
        return match ($this) {
            self::AlbumPublished, self::ActivityPublished, self::DataExportReady,
            self::ActivityAwaitingReview => null,
            self::SignIn => 'New sign-in to your GEWIS account',
            self::PasswordChanged => 'Your GEWIS password was changed',
            self::MfaEnabled => 'Two-factor authentication enabled on your GEWIS account',
            self::MfaDisabled => 'Two-factor authentication disabled on your GEWIS account',
            self::BackupCodesRegenerated => 'New backup codes for your GEWIS account',
        };
    }
}
