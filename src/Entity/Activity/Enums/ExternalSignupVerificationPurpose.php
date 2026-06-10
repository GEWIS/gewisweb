<?php

declare(strict_types=1);

namespace App\Entity\Activity\Enums;

/**
 * What an {@see \App\Entity\Activity\ExternalSignupVerification} token authorises.
 */
enum ExternalSignupVerificationPurpose: string
{
    /**
     * A short-lived double-opt-in token: while a live one exists the {@see \App\Entity\Activity\ExternalSignup} counts
     * as unverified (hidden from lists/counts/admission). Clicking the link deletes the token, confirming the sign-up.
     * Its expiry is also the prune deadline for an unconfirmed sign-up.
     */
    case Verify = 'verify';

    /**
     * A long-lived capability token emailed once the sign-up is confirmed, letting the external participant edit or
     * unsubscribe themselves (they have no account). Reusable until the sign-up is withdrawn.
     */
    case Manage = 'manage';

    /**
     * The route the token link points at. Exhaustive (no default) so adding a purpose forces a deliberate mapping.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Verify => 'activity/external_signup_verify',
            self::Manage => 'activity/external_signup_manage',
        };
    }

    /**
     * The Twig template for the token e-mail.
     */
    public function emailTemplate(): string
    {
        return match ($this) {
            self::Verify => 'emails/activity/external-signup-verification.html.twig',
            self::Manage => 'emails/activity/external-signup-manage.html.twig',
        };
    }

    /**
     * The sprintf format for the (always-English) e-mail subject; `%1$s` is the activity name, `%2$s` the list name.
     */
    public function subjectFormat(): string
    {
        return match ($this) {
            self::Verify => 'Confirm your sign-up for %s (%s)',
            self::Manage => 'Manage your sign-up for %s (%s)',
        };
    }
}
