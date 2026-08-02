<?php

declare(strict_types=1);

namespace App\Service\Decision;

use DateTimeImmutable;

/**
 * What {@see LegacyDocumentNameParser} could read from one legacy document name.
 */
final readonly class ParsedLegacyName
{
    public function __construct(
        /** The agenda point number, or null for a meeting-level document. */
        public ?string $pointNumber,
        /** The display name with point prefixes and version/date suffixes stripped. */
        public string $baseName,
        /** The normalised base name used to group versions of the same document. */
        public string $groupKey,
        /** The version label embedded in the name, e.g. "v1.2". */
        public ?string $versionLabel,
        /** The upload date embedded in the name. */
        public ?DateTimeImmutable $versionDate,
        /** The reference-library key for known recurring documents. */
        public ?string $referenceKey,
    ) {
    }
}
