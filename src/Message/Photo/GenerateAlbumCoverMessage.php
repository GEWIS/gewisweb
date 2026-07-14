<?php

declare(strict_types=1);

namespace App\Message\Photo;

/**
 * Requests asynchronous (re)generation of an album's cover mosaic. Dispatched when the album's photo set changes.
 * Carries only the album id (a scalar), so the message stays small and re-resolves fresh state when handled.
 */
class GenerateAlbumCoverMessage
{
    public function __construct(
        private readonly int $albumId,
    ) {
    }

    public function getAlbumId(): int
    {
        return $this->albumId;
    }
}
