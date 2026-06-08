<?php

declare(strict_types=1);

namespace App\Service\Application;

use JsonException;
use Psr\Cache\CacheItemPoolInterface;

use function base64_decode;
use function hash;
use function is_array;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Single-use guard for solved Altcha proof-of-work captchas. The local (non-Sentinel) Altcha validator only checks the
 * HMAC signature, the expiry and the proof-of-work -- it does NOT stop a solved payload being submitted more than once
 * within its signature-validity window. This consumes a verified solution by remembering its challenge signature until
 * that window passes, so replaying the same solved proof-of-work is rejected.
 */
final readonly class AltchaSolutionGuard
{
    private const string CACHE_PREFIX = 'altcha_solution_';

    // Covers the Altcha challenge validity window (config/packages/altcha.yaml `expires: +15 minutes`). Once the
    // signature has expired the validator rejects the solution anyway, so the marker may expire with it.
    private const int TTL_SECONDS = 900;

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Mark an already cryptographically-verified Altcha payload as used. Returns false when it was already used within
     * its validity window (a replay) or cannot be parsed; true on first use. Call this only AFTER the Altcha
     * constraint has validated the payload.
     */
    public function consume(?string $payload): bool
    {
        $signature = $this->signatureOf($payload);
        if (null === $signature) {
            return false;
        }

        $item = $this->cache->getItem(self::CACHE_PREFIX . hash('sha256', $signature));
        if ($item->isHit()) {
            return false;
        }

        $item->set(true);
        $item->expiresAfter(self::TTL_SECONDS);
        $this->cache->save($item);

        return true;
    }

    private function signatureOf(?string $payload): ?string
    {
        if (
            null === $payload
            || '' === $payload
        ) {
            return null;
        }

        $decoded = base64_decode(
            $payload,
            true,
        );
        if (false === $decoded) {
            return null;
        }

        try {
            $data = json_decode(
                $decoded,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return null;
        }

        $challenge = is_array($data)
            ? ($data['challenge'] ?? null)
            : null;
        $signature = is_array($challenge)
            ? ($challenge['signature'] ?? null)
            : null;

        return is_string($signature) && '' !== $signature
            ? $signature
            : null;
    }
}
