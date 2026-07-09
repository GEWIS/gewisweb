<?php

declare(strict_types=1);

namespace App\Util\Application;

use function bin2hex;
use function count;
use function explode;
use function hash;
use function hash_equals;
use function max;
use function random_bytes;

/**
 * The shared "split token" scheme used for emailed, single-use links (external sign-up verify/manage, password reset):
 * a public token of the form {@code selector.verifier}, of which only the selector and a hash of the verifier are kept.
 * The selector indexes the row; the verifier is hash-compared in constant time, so a leaked selector alone is useless
 * and the stored hash never reveals the token. The hash algorithm comes from each entity's own {@code HASH_ALGO}.
 */
final class SplitToken
{
    /**
     * Generate a fresh token: a random selector and verifier (hex-encoded), the verifier's hash to store, and the
     * public {@code selector.verifier} string to email.
     *
     * @return array{selector: string, hashedToken: string, token: string}
     */
    public static function generate(
        int $selectorBytes,
        int $verifierBytes,
        string $hashAlgo,
    ): array {
        // max(1, …) keeps random_bytes' length strictly positive (and satisfies its int<1, max> contract).
        $selector = bin2hex(random_bytes(max(1, $selectorBytes)));
        $verifier = bin2hex(random_bytes(max(1, $verifierBytes)));

        return [
            'selector' => $selector,
            'hashedToken' => hash(
                $hashAlgo,
                $verifier,
            ),
            'token' => $selector . '.' . $verifier,
        ];
    }

    /**
     * Split a public token into its selector and verifier, or null when it is not exactly {@code selector.verifier}.
     *
     * @return array{selector: string, verifier: string}|null
     */
    public static function split(string $token): ?array
    {
        $parts = explode(
            '.',
            $token,
            2,
        );
        if (2 !== count($parts)) {
            return null;
        }

        return [
            'selector' => $parts[0],
            'verifier' => $parts[1],
        ];
    }

    /**
     * Whether the presented verifier hashes (in constant time) to the stored hash.
     */
    public static function matches(
        string $storedHash,
        string $verifier,
        string $hashAlgo,
    ): bool {
        return hash_equals(
            $storedHash,
            hash(
                $hashAlgo,
                $verifier,
            ),
        );
    }
}
