<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Service\Application\ImageSigner;
use PHPUnit\Framework\TestCase;

use function hash_hmac;
use function sprintf;
use function time;

/**
 * The signer produces and validates the signed serving URLs for private images. These verify that a freshly signed
 * URL validates for exactly its variant and path, that any tampering is rejected in constant time, and that a
 * correctly signed but expired URL no longer validates (the security-critical part).
 */
final class ImageSignerTest extends TestCase
{
    private const string KEY = 'test-signing-key';
    private const string PATH = 'photos/albums/ab/abcdef.jpg';

    public function testAFreshSignatureValidatesForItsVariantAndPath(): void
    {
        $signer = new ImageSigner(self::KEY);
        [
            $expires, $signature
        ] = $signer->sign(
            'w320',
            self::PATH,
        );

        self::assertGreaterThan(
            time(),
            $expires,
            'The expiry should be in the future (next midnight).',
        );
        self::assertTrue($signer->isValid('w320', self::PATH, $expires, $signature));
    }

    public function testATamperedSignatureIsRejected(): void
    {
        $signer = new ImageSigner(self::KEY);
        [$expires] = $signer->sign(
            'w320',
            self::PATH,
        );

        self::assertFalse($signer->isValid('w320', self::PATH, $expires, 'not-the-signature'));
    }

    public function testASignatureDoesNotTransferToAnotherVariant(): void
    {
        $signer = new ImageSigner(self::KEY);
        [
            $expires, $signature
        ] = $signer->sign(
            'w320',
            self::PATH,
        );

        self::assertFalse($signer->isValid('w640', self::PATH, $expires, $signature));
    }

    public function testASignatureDoesNotTransferToAnotherPath(): void
    {
        $signer = new ImageSigner(self::KEY);
        [
            $expires, $signature
        ] = $signer->sign(
            'w320',
            self::PATH,
        );

        self::assertFalse($signer->isValid('w320', 'photos/albums/cd/other.jpg', $expires, $signature));
    }

    public function testACorrectlySignedButExpiredUrlIsRejected(): void
    {
        $signer = new ImageSigner(self::KEY);
        $expired = time() - 3600;
        // Build a valid signature for a past expiry, replicating the signer's HMAC, to isolate the expiry check.
        $signature = hash_hmac(
            'sha3-256',
            sprintf(
                'w320|%s|%d',
                self::PATH,
                $expired,
            ),
            self::KEY,
        );

        self::assertFalse($signer->isValid('w320', self::PATH, $expired, $signature));
    }
}
