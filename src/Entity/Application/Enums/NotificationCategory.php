<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How notifications are grouped where a member is shown them as a topic rather than one at a time.
 *
 * Several kinds can share one: a password change and a second factor being turned on are one thing to a member, even
 * though they are separate events with their own sentences.
 */
enum NotificationCategory implements TranslatableInterface
{
    case Albums;
    case Activities;
    case SignIns;
    case AccountSecurity;
    case DataExports;
    case ActivityReviews;
    case SignupReminders;

    public function icon(): string
    {
        return match ($this) {
            self::Albums => 'fa-images',
            self::Activities => 'fa-calendar-day',
            self::SignIns => 'fa-right-to-bracket',
            self::AccountSecurity => 'fa-shield-halved',
            self::DataExports => 'fa-file-arrow-down',
            self::ActivityReviews => 'fa-clipboard-check',
            self::SignupReminders => 'fa-hourglass-half',
        };
    }

    /**
     * How a topic that cannot be turned off reaches a member, so the settings page does not imply an email that never
     * comes.
     */
    public function delivery(): TranslatableMessage
    {
        return match ($this) {
            self::SignIns, self::AccountSecurity, self::DataExports => new TranslatableMessage('Website and email'),
            self::SignupReminders => new TranslatableMessage('Website only'),
            self::Albums, self::Activities, self::ActivityReviews => new TranslatableMessage('Website'),
        };
    }

    /**
     * A short line under the title, explaining when it fires.
     */
    public function hint(): TranslatableMessage
    {
        return match ($this) {
            self::Albums => new TranslatableMessage('When photos of an event are published'),
            self::Activities => new TranslatableMessage('New activities you can sign up for'),
            self::SignIns => new TranslatableMessage('Every time your account is signed in'),
            self::AccountSecurity => new TranslatableMessage('When the way you sign in changes'),
            self::DataExports => new TranslatableMessage('When a data export you asked for is ready'),
            self::ActivityReviews => new TranslatableMessage('When an activity is waiting to be reviewed'),
            self::SignupReminders => new TranslatableMessage('Shortly before a sign-up you are on closes'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return match ($this) {
            self::Albums => $translator->trans(
                'New photo albums',
                locale: $locale,
            ),
            self::Activities => $translator->trans(
                'New activities',
                locale: $locale,
            ),
            self::SignIns => $translator->trans(
                'Sign-ins',
                locale: $locale,
            ),
            self::AccountSecurity => $translator->trans(
                'Account security',
                locale: $locale,
            ),
            self::DataExports => $translator->trans(
                'Data exports',
                locale: $locale,
            ),
            self::ActivityReviews => $translator->trans(
                'Activities awaiting review',
                locale: $locale,
            ),
            self::SignupReminders => $translator->trans(
                'Sign-up reminders',
                locale: $locale,
            ),
        };
    }
}
