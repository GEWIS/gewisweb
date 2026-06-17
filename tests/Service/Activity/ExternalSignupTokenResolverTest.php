<?php

declare(strict_types=1);

namespace App\Tests\Service\Activity;

use App\Entity\Activity\Enums\ExternalSignupVerificationPurpose;
use App\Entity\Activity\ExternalSignupVerification;
use App\Repository\Activity\ExternalSignupVerificationRepository;
use App\Service\Activity\ExternalSignupTokenResolver;
use PHPUnit\Framework\TestCase;

use function hash;

/**
 * The security boundary for emailed external-sign-up links: an emailed `selector.verifier` token resolves to a live
 * verification only when the selector is known, the purpose matches, it has not expired, and the verifier hash-matches
 * (constant-time). Every other case must resolve to null so a forged, stale, or wrong-purpose link is never trusted.
 */
final class ExternalSignupTokenResolverTest extends TestCase
{
    public function testResolvesAValidToken(): void
    {
        $verification = $this->verification(
            ExternalSignupVerificationPurpose::Verify,
            expired: false,
            verifier: 'verifier-secret',
        );
        $resolver = $this->resolverFinding($verification);

        self::assertSame(
            $verification,
            $resolver->resolve(
                'selector-1.verifier-secret',
                ExternalSignupVerificationPurpose::Verify,
            ),
        );
    }

    public function testRejectsAMalformedTokenWithoutASeparator(): void
    {
        $resolver = $this->resolverFinding(null);

        self::assertNull($resolver->resolve(
            'no-separator',
            ExternalSignupVerificationPurpose::Verify,
        ));
    }

    public function testRejectsAnUnknownSelector(): void
    {
        $resolver = $this->resolverFinding(null);

        self::assertNull($resolver->resolve(
            'selector-1.verifier-secret',
            ExternalSignupVerificationPurpose::Verify,
        ));
    }

    public function testRejectsAMismatchedPurpose(): void
    {
        $verification = $this->verification(
            ExternalSignupVerificationPurpose::Verify,
            expired: false,
            verifier: 'verifier-secret',
        );
        $resolver = $this->resolverFinding($verification);

        self::assertNull($resolver->resolve(
            'selector-1.verifier-secret',
            ExternalSignupVerificationPurpose::Manage,
        ));
    }

    public function testRejectsAnExpiredVerification(): void
    {
        $verification = $this->verification(
            ExternalSignupVerificationPurpose::Verify,
            expired: true,
            verifier: 'verifier-secret',
        );
        $resolver = $this->resolverFinding($verification);

        self::assertNull($resolver->resolve(
            'selector-1.verifier-secret',
            ExternalSignupVerificationPurpose::Verify,
        ));
    }

    public function testRejectsAVerifierThatDoesNotHashMatch(): void
    {
        $verification = $this->verification(
            ExternalSignupVerificationPurpose::Verify,
            expired: false,
            verifier: 'the-real-verifier',
        );
        $resolver = $this->resolverFinding($verification);

        self::assertNull($resolver->resolve(
            'selector-1.a-different-verifier',
            ExternalSignupVerificationPurpose::Verify,
        ));
    }

    private function resolverFinding(?ExternalSignupVerification $found): ExternalSignupTokenResolver
    {
        $repository = self::createStub(ExternalSignupVerificationRepository::class);
        $repository->method('findBySelector')->willReturn($found);

        return new ExternalSignupTokenResolver($repository);
    }

    private function verification(
        ExternalSignupVerificationPurpose $purpose,
        bool $expired,
        string $verifier,
    ): ExternalSignupVerification {
        $verification = self::createStub(ExternalSignupVerification::class);
        $verification->method('getPurpose')->willReturn($purpose);
        $verification->method('isExpired')->willReturn($expired);
        $verification->method('getHashedToken')->willReturn(hash(
            ExternalSignupVerification::HASH_ALGO,
            $verifier,
        ));

        return $verification;
    }
}
