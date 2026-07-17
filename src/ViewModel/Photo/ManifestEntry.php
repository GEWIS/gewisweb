<?php

declare(strict_types=1);

namespace App\ViewModel\Photo;

/**
 * One photo in an album's viewer manifest, the shape the PhotoSwipe viewer consumes as its data source. The whole album
 * is sent at once so a `#pid` deep link resolves even when the grid has only rendered the first page of thumbnails.
 *
 * The photo entity stores only an aspect ratio, not the original pixel size, so `w`/`h` are the large variant's
 * reference width and its aspect-derived height. That is enough for the viewer to lay out and size each slide.
 */
final readonly class ManifestEntry
{
    public function __construct(
        public int $id,
        public int $w,
        public int $h,
        public string $thumbUrl,
        public string $largeUrl,
        public string $xlargeUrl,
        public string $downloadUrl,
        // The deep link to the photo's real album, set only for virtual albums (e.g. the weekly album) so the viewer
        // can offer a "go to the original album" button; null when already viewing that album.
        public ?string $albumUrl = null,
        // Whether this photo is hidden from the member's own photo page (flagged only in that member's own view, so the
        // grid can grey it out).
        public bool $hidden = false,
    ) {
    }
}
