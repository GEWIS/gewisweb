<?php

declare(strict_types=1);

namespace App\Util\Application;

use function preg_match;

/**
 * What a slug may look like anywhere it stands in for a name in a URL. Slugs stay lower-case and stick to letters,
 * digits, underscores and hyphens, and must start with a letter: one that starts with a digit or a hyphen reads as
 * something else in a path, and one that differs from another only in casing is a different URL for the same thing.
 */
final class SlugRule
{
    /**
     * Anchored with `\A` and `\z` rather than `^` and `$`, which would let a trailing newline through and put a
     * character in a public URL that has no business being there.
     */
    public const string PATTERN = '/\A[a-z][0-9a-z_\-]*\z/';

    /**
     * The route requirement form of {@see self::PATTERN}, without the delimiters and anchors a route adds itself.
     */
    public const string ROUTE_REQUIREMENT = '[a-z][0-9a-z_\-]*';

    public static function matches(string $slug): bool
    {
        return 1 === preg_match(
            self::PATTERN,
            $slug,
        );
    }
}
