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
}
