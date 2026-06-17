<?php

declare(strict_types=1);

namespace App\Tests\Service\Application;

use App\Service\Application\AltchaSolutionGuard;
use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function base64_encode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * The local Altcha validator only checks the signature, expiry and proof-of-work -- it does not stop a solved payload
 * being submitted twice within its validity window. This guard closes that replay hole by remembering a consumed
 * challenge signature, so the first submission is accepted and any replay (same signature) is rejected. A payload it
 * cannot parse down to a challenge signature is treated as not-a-solution (false), never accidentally accepted.
 */
final class AltchaSolutionGuardTest extends TestCase
{
    private AltchaSolutionGuard $guard;

    #[Override]
    protected function setUp(): void
    {
        $this->guard = new AltchaSolutionGuard(new ArrayAdapter());
    }

    public function testAcceptsAFreshlySolvedPayloadOnce(): void
    {
        self::assertTrue($this->guard->consume($this->payloadSignedWith('signature-abc')));
    }

    public function testRejectsReplayingTheSameSolvedPayload(): void
    {
        $payload = $this->payloadSignedWith('signature-abc');

        self::assertTrue($this->guard->consume($payload));
        self::assertFalse($this->guard->consume($payload));
    }

    public function testTreatsDistinctChallengeSignaturesIndependently(): void
    {
        self::assertTrue($this->guard->consume($this->payloadSignedWith('signature-a')));
        self::assertTrue($this->guard->consume($this->payloadSignedWith('signature-b')));
    }

    public function testRejectsANullOrEmptyPayload(): void
    {
        self::assertFalse($this->guard->consume(null));
        self::assertFalse($this->guard->consume(''));
    }

    public function testRejectsAnUnparseableOrIncompletePayload(): void
    {
        // Not valid base64, valid base64 that is not JSON, and valid JSON without a challenge signature.
        self::assertFalse($this->guard->consume('%%% not base64 %%%'));
        self::assertFalse($this->guard->consume(base64_encode('not json at all')));
        self::assertFalse($this->guard->consume($this->encode(['challenge' => ['unrelated' => 'x']])));
    }

    private function payloadSignedWith(string $signature): string
    {
        return $this->encode(['challenge' => ['signature' => $signature]]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function encode(array $data): string
    {
        return base64_encode(json_encode(
            $data,
            JSON_THROW_ON_ERROR,
        ));
    }
}
