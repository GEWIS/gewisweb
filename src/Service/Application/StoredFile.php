<?php

declare(strict_types=1);

namespace App\Service\Application;

/**
 * The result of storing a file through {@see FileStorage}: where it landed and what it is. Immutable; carries the
 * content hash so callers can persist the stored path on their entity and reason about de-duplication.
 */
final readonly class StoredFile
{
    public function __construct(
        /** The stored path relative to the storage root, for example `photos/albums/ab/ab34f0.jpg`. */
        public string $path,
        /** The lowercase hex content hash (sha256) the path was derived from. */
        public string $hash,
        /** The detected (not client-supplied) MIME type of the stored content. */
        public string $mimeType,
        /** The size of the stored content, in bytes. */
        public int $size,
        /**
         * Whether the content already existed under this path and the write was skipped (content-addressed
         * de-duplication). `false` means the bytes were freshly written.
         */
        public bool $deduplicated,
    ) {
    }
}
