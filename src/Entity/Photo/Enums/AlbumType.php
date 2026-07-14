<?php

declare(strict_types=1);

namespace App\Entity\Photo\Enums;

/**
 * The kinds of album the browsing routes accept. `Regular` is a stored {@see \App\Entity\Photo\Album}; the others are
 * virtual albums: `Member` (a member's tagged photos), `Weekly` (the photos of the week) and `Body` (the photos an
 * organ is tagged in, the counterpart of organ tagging, GH-1992). The backing values are the `{type}` segment in the
 * photo album URLs.
 */
enum AlbumType: string
{
    case Regular = 'album';
    case Member = 'member';
    case Weekly = 'weekly';
    case Body = 'body';
}
