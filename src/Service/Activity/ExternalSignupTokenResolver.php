<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignupVerification;
use App\Repository\Activity\ExternalSignupVerificationRepository;

use function count;
use function explode;
use function hash;
use function hash_equals;

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
        $parts = explode(
            '.',
            $token,
            2,
        );
        if (2 !== count($parts)) {
            return null;
        }

        [
            $selector, $verifier
        ] = $parts;
        $verification = $this->verificationRepository->findBySelector($selector);
        if (
            null === $verification
            || $verification->getPurpose() !== $purpose
            || $verification->isExpired()
        ) {
            return null;
        }

        if (
            !hash_equals(
                $verification->getHashedToken(),
                hash(
                    ExternalSignupVerification::HASH_ALGO,
                    $verifier,
                ),
            )
        ) {
            return null;
        }

        return $verification;
    }
}
