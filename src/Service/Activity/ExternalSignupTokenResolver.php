<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignupVerification;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\Util\Application\SplitToken;

/**
 * Resolves an emailed `selector.verifier` external-sign-up token to a live verification of the expected purpose, or
 * null on any failure (unknown selector, wrong purpose, expired, or a verifier that does not hash-match). Shared by the
 * verify controller and the self-service manage live component so the security-sensitive lookup is identical and is
 * re-run on every request; a once-validated token is never trusted.
 */
final readonly class ExternalSignupTokenResolver
{
    public function __construct(
        private ExternalSignupVerificationRepository $verificationRepository,
    ) {
    }

    public function resolve(
        string $token,
        ExternalSignupVerificationPurpose $purpose,
    ): ?ExternalSignupVerification {
        $split = SplitToken::split($token);
        if (null === $split) {
            return null;
        }

        $verification = $this->verificationRepository->findBySelector($split['selector']);
        if (
            null === $verification
            || $verification->getPurpose() !== $purpose
            || $verification->isExpired()
        ) {
            return null;
        }

        if (
            !SplitToken::matches(
                $verification->getHashedToken(),
                $split['verifier'],
                ExternalSignupVerification::HASH_ALGO,
            )
        ) {
            return null;
        }

        return $verification;
    }
}
