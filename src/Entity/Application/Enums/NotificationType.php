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
    case SignupClosing = 'signup_closing';
    case SignupClosingWithFields = 'signup_closing_with_fields';
    case CompanyRevisionAwaitingReview = 'company_revision_awaiting_review';
    case VacancyRevisionAwaitingReview = 'vacancy_revision_awaiting_review';
    case CompanyBannerAwaitingReview = 'company_banner_awaiting_review';
    case OrganInformationRevisionAwaitingReview = 'organ_information_revision_awaiting_review';
    case PollRevisionAwaitingReview = 'poll_revision_awaiting_review';
    case ActivityProposalAwaitingDecision = 'proposal_awaiting_decision';
    case ActivityProposalScheduled = 'activity_proposal_scheduled';
    case ActivityProposalDeclined = 'activity_proposal_declined';
    case ActivityProposalBudgetDue = 'activity_proposal_budget_due';
    case ActivityProposalLapsed = 'activity_proposal_lapsed';

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
            self::SignupClosing, self::SignupClosingWithFields => 'fa-hourglass-half',
            self::CompanyRevisionAwaitingReview, self::VacancyRevisionAwaitingReview => 'fa-clipboard-check',
            self::CompanyBannerAwaitingReview => 'fa-image',
            self::OrganInformationRevisionAwaitingReview => 'fa-clipboard-check',
            self::PollRevisionAwaitingReview => 'fa-square-poll-vertical',
            self::ActivityProposalAwaitingDecision => 'fa-calendar-plus',
            self::ActivityProposalScheduled => 'fa-calendar-check',
            self::ActivityProposalDeclined => 'fa-calendar-xmark',
            self::ActivityProposalBudgetDue => 'fa-hourglass-half',
            self::ActivityProposalLapsed => 'fa-calendar-xmark',
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
            self::SignupClosing, self::SignupClosingWithFields => NotificationCategory::SignupReminders,
            self::CompanyRevisionAwaitingReview, self::VacancyRevisionAwaitingReview,
            self::CompanyBannerAwaitingReview => NotificationCategory::CareerReviews,
            self::OrganInformationRevisionAwaitingReview => NotificationCategory::BodyReviews,
            self::PollRevisionAwaitingReview => NotificationCategory::PollReviews,
            self::ActivityProposalAwaitingDecision, self::ActivityProposalScheduled,
            self::ActivityProposalDeclined, self::ActivityProposalBudgetDue,
            self::ActivityProposalLapsed => NotificationCategory::OptionCalendar,
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
            self::SignupClosing, self::SignupClosingWithFields => NotificationAddressing::Account,
            self::CompanyRevisionAwaitingReview, self::VacancyRevisionAwaitingReview,
            self::CompanyBannerAwaitingReview => NotificationAddressing::Role,
            self::OrganInformationRevisionAwaitingReview, self::PollRevisionAwaitingReview
                => NotificationAddressing::Role,
            self::ActivityProposalAwaitingDecision => NotificationAddressing::Role,
            self::ActivityProposalScheduled, self::ActivityProposalDeclined,
            self::ActivityProposalBudgetDue, self::ActivityProposalLapsed => NotificationAddressing::Account,
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
            self::SignupClosing, self::SignupClosingWithFields => 'activity/view',
            self::CompanyRevisionAwaitingReview => 'admin/career/approvals/company',
            self::VacancyRevisionAwaitingReview => 'admin/career/approvals/vacancy',
            self::CompanyBannerAwaitingReview => 'admin/career/approvals/index',
            self::OrganInformationRevisionAwaitingReview => 'admin/decision/bodies/approvals/review',
            self::PollRevisionAwaitingReview => 'admin/frontpage/polls/approvals/review',
            self::ActivityProposalAwaitingDecision => 'admin/activities/calendar/decisions/index',
            self::ActivityProposalScheduled, self::ActivityProposalDeclined,
            self::ActivityProposalBudgetDue,
            self::ActivityProposalLapsed => 'admin/activities/calendar/proposal',
        };
    }

    /**
     * Where a run of these points when they are shown as one line: the list they all belong to, since no single one of
     * them is the thing the reader wants.
     */
    public function manyRoute(?Firewall $recipient = null): string
    {
        return match ($this) {
            self::AlbumPublished => 'photo/index',
            self::ActivityPublished => 'activity/index',
            self::ActivityAwaitingReview => 'admin/activities/approvals/index',
            self::SignupClosing, self::SignupClosingWithFields => 'activity/index',
            self::SignIn, self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated => ($recipient ?? Firewall::Main)->securityIndexRoute(),
            self::DataExportReady => 'user_settings_data_export_download',
            self::CompanyRevisionAwaitingReview, self::VacancyRevisionAwaitingReview,
            self::CompanyBannerAwaitingReview => 'admin/career/approvals/index',
            self::OrganInformationRevisionAwaitingReview => 'admin/decision/bodies/approvals/index',
            self::PollRevisionAwaitingReview => 'admin/frontpage/polls/approvals/index',
            self::ActivityProposalAwaitingDecision => 'admin/activities/calendar/decisions/index',
            self::ActivityProposalScheduled, self::ActivityProposalDeclined,
            self::ActivityProposalBudgetDue,
            self::ActivityProposalLapsed => 'admin/activities/calendar/index',
        };
    }

    /**
     * What a run of these reads as when they are shown as one line.
     */
    public function manyMessage(int $count): TranslatableMessage
    {
        return match ($this) {
            self::AlbumPublished => new TranslatableMessage(
                '%count% new photo albums are online.',
                ['%count%' => $count],
            ),
            self::ActivityPublished => new TranslatableMessage(
                '%count% new activities have been published.',
                ['%count%' => $count],
            ),
            self::ActivityAwaitingReview => new TranslatableMessage(
                '%count% activities have been submitted for review.',
                ['%count%' => $count],
            ),
            self::SignIn => new TranslatableMessage(
                'Your account was signed in %count% times.',
                ['%count%' => $count],
            ),
            self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated => new TranslatableMessage(
                'The way you sign in changed %count% times.',
                ['%count%' => $count],
            ),
            self::DataExportReady => new TranslatableMessage(
                '%count% data exports you asked for are ready.',
                ['%count%' => $count],
            ),
            self::SignupClosing, self::SignupClosingWithFields => new TranslatableMessage(
                '%count% sign-ups you are on are closing soon.',
                ['%count%' => $count],
            ),
            self::CompanyRevisionAwaitingReview => new TranslatableMessage(
                '%count% company profiles have been submitted for review.',
                ['%count%' => $count],
            ),
            self::VacancyRevisionAwaitingReview => new TranslatableMessage(
                '%count% vacancies have been submitted for review.',
                ['%count%' => $count],
            ),
            self::CompanyBannerAwaitingReview => new TranslatableMessage(
                '%count% banners are waiting for a decision.',
                ['%count%' => $count],
            ),
            self::OrganInformationRevisionAwaitingReview => new TranslatableMessage(
                '%count% bodies have submitted their page for review.',
                ['%count%' => $count],
            ),
            self::PollRevisionAwaitingReview => new TranslatableMessage(
                '%count% polls have been requested.',
                ['%count%' => $count],
            ),
            self::ActivityProposalAwaitingDecision => new TranslatableMessage(
                '%count% bodies are waiting for a day.',
                ['%count%' => $count],
            ),
            self::ActivityProposalScheduled => new TranslatableMessage(
                '%count% of your proposals have been given a day.',
                ['%count%' => $count],
            ),
            self::ActivityProposalDeclined => new TranslatableMessage(
                '%count% of your proposals have been turned down.',
                ['%count%' => $count],
            ),
            self::ActivityProposalBudgetDue => new TranslatableMessage(
                '%count% of the days you are holding are at risk.',
                ['%count%' => $count],
            ),
            self::ActivityProposalLapsed => new TranslatableMessage(
                '%count% of the days you were holding have been released.',
                ['%count%' => $count],
            ),
        };
    }

    /**
     * @param array<string, string> $context
     *
     * @return array<string, int|string>
     */
    public function routeParameters(
        ?int $subjectId,
        array $context = [],
    ): array {
        // Several reminders are about the same activity, so they cannot key on a subject the way an announcement
        // does. What they point at travels with them instead.
        if (
            self::SignupClosing === $this
            || self::SignupClosingWithFields === $this
        ) {
            return isset($context['activity'])
                ? ['activity' => $context['activity']]
                : [];
        }

        // A proposal can be given a day, lose it and be given one again, so these cannot key on a subject either: the
        // unique index over (type, subjectId) would refuse the second one.
        if (
            self::ActivityProposalScheduled === $this
            || self::ActivityProposalDeclined === $this
            || self::ActivityProposalBudgetDue === $this
            || self::ActivityProposalLapsed === $this
        ) {
            return isset($context['proposal'])
                ? ['proposal' => $context['proposal']]
                : [];
        }

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
            self::ActivityAwaitingReview, self::CompanyRevisionAwaitingReview,
            self::VacancyRevisionAwaitingReview, self::OrganInformationRevisionAwaitingReview,
            self::PollRevisionAwaitingReview => ['revision' => $subjectId],
            self::CompanyBannerAwaitingReview => [],
            self::SignIn, self::PasswordChanged, self::MfaEnabled,
            self::MfaDisabled, self::BackupCodesRegenerated, self::DataExportReady => [],
            // The other four never reach here: they carry the proposal in their context and returned above.
            self::ActivityProposalAwaitingDecision => [],
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
            self::SignupClosing, self::SignupClosingWithFields => new TranslatableMessage('View the activity'),
            self::ActivityAwaitingReview, self::CompanyRevisionAwaitingReview,
            self::VacancyRevisionAwaitingReview, self::OrganInformationRevisionAwaitingReview,
            self::PollRevisionAwaitingReview => new TranslatableMessage('Review it'),
            self::CompanyBannerAwaitingReview => new TranslatableMessage('View the banners'),
            self::ActivityProposalAwaitingDecision => new TranslatableMessage('Decide'),
            self::ActivityProposalScheduled, self::ActivityProposalDeclined,
            self::ActivityProposalBudgetDue,
            self::ActivityProposalLapsed => new TranslatableMessage('View the proposal'),
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
            self::SignupClosing => new TranslatableMessage(
                'Sign-ups for "%name%" close soon. You will not be able to withdraw after that.',
                ['%name%' => $name],
            ),
            self::SignupClosingWithFields => new TranslatableMessage(
                'Sign-ups for "%name%" close soon. You will not be able to withdraw or change your answers after '
                . 'that.',
                ['%name%' => $name],
            ),
            self::ActivityAwaitingReview => new TranslatableMessage(
                'The activity "%name%" has been submitted for review.',
                ['%name%' => $name],
            ),
            self::CompanyRevisionAwaitingReview => new TranslatableMessage(
                '%name% has submitted a new profile for review.',
                ['%name%' => $name],
            ),
            self::VacancyRevisionAwaitingReview => new TranslatableMessage(
                'The vacancy "%name%" has been submitted for review.',
                ['%name%' => $name],
            ),
            self::CompanyBannerAwaitingReview => new TranslatableMessage(
                '%name% has proposed a new banner.',
                ['%name%' => $name],
            ),
            self::OrganInformationRevisionAwaitingReview => new TranslatableMessage(
                '%name% has submitted changes to its page for review.',
                ['%name%' => $name],
            ),
            self::PollRevisionAwaitingReview => new TranslatableMessage(
                'A poll asking "%name%" has been requested.',
                ['%name%' => $name],
            ),
            self::ActivityProposalAwaitingDecision => new TranslatableMessage(
                '"%name%" has been put forward and is waiting for a day.',
                ['%name%' => $name],
            ),
            self::ActivityProposalScheduled => new TranslatableMessage(
                '"%name%" has been given a day. The activity has been started for you and still needs filling in.',
                ['%name%' => $name],
            ),
            self::ActivityProposalDeclined => new TranslatableMessage(
                '"%name%" has been turned down; its days are free again.',
                ['%name%' => $name],
            ),
            self::ActivityProposalBudgetDue => new TranslatableMessage(
                'The day you are holding for "%name%" is at risk: the board has not recorded a budget for it, or '
                . 'that it needs none.',
                ['%name%' => $name],
            ),
            self::ActivityProposalLapsed => new TranslatableMessage(
                'The day you were holding for "%name%" has been released, because nothing was settled about its '
                . 'budget in time.',
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
            self::ActivityAwaitingReview, self::SignupClosing, self::SignupClosingWithFields,
            self::CompanyRevisionAwaitingReview, self::VacancyRevisionAwaitingReview,
            self::CompanyBannerAwaitingReview, self::OrganInformationRevisionAwaitingReview,
            self::PollRevisionAwaitingReview, self::ActivityProposalAwaitingDecision,
            self::ActivityProposalScheduled, self::ActivityProposalDeclined,
            self::ActivityProposalBudgetDue, self::ActivityProposalLapsed => null,
            self::SignIn => 'New sign-in to your GEWIS account',
            self::PasswordChanged => 'Your GEWIS password was changed',
            self::MfaEnabled => 'Two-factor authentication enabled on your GEWIS account',
            self::MfaDisabled => 'Two-factor authentication disabled on your GEWIS account',
            self::BackupCodesRegenerated => 'New backup codes for your GEWIS account',
        };
    }
}
