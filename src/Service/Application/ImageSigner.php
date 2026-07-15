<?php

declare(strict_types=1);

namespace App\Service\Application;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function hash_equals;
use function hash_hmac;
use function sprintf;
use function time;

/**
 * Signs and verifies the private (members-only) image URLs. A signature is an HMAC over the variant, the source path
 * and an expiry set to the next midnight, so a given image's URL is byte-identical for the whole day (the browser
 * caches it like the legacy Glide URLs did), yet a leaked URL stops working within a day.
 *
 * The signature only ever affects URLs: stored files and the variant cache are never recomputed when the key changes,
 * so rotating the key is safe. The signature is domain-separated (variant|path|expires) and compared in constant
 * time. It is one half of the private-namespace gate; the other half (a logged-in session and, for graduates, a
 * per-photo access check) is enforced at serving time.
 */
final readonly class ImageSigner
{
    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private string $signingKey,
    ) {
    }

    /**
     * The `expires` (next-midnight unix timestamp) and hex signature for a variant of a source path.
     *
     * @return array{int, string}
     */
    public function sign(
        string $variant,
        string $path,
    ): array {
        $expires = $this->nextMidnight();

        return [
            $expires,
            $this->compute(
                $variant,
                $path,
                $expires,
            ),
        ];
    }

    /**
     * Whether the given signature is valid for the variant/path and has not expired.
     */
    public function isValid(
        string $variant,
        string $path,
        int $expires,
        string $signature,
    ): bool {
        if ($expires < time()) {
            return false;
        }

        return hash_equals(
            $this->compute(
                $variant,
                $path,
                $expires,
            ),
            $signature,
        );
    }

    private function compute(
        string $variant,
        string $path,
        int $expires,
    ): string {
        return hash_hmac(
            'sha3-256',
            sprintf(
                '%s|%s|%d',
                $variant,
                $path,
                $expires,
            ),
            $this->signingKey,
        );
    }

    /**
     * The next midnight (local time) as a unix timestamp, so every URL minted today shares one expiry.
     */
    private function nextMidnight(): int
    {
        return new DateTimeImmutable('tomorrow midnight')->getTimestamp();
    }
}
