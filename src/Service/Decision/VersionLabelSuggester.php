<?php

declare(strict_types=1);

namespace App\Service\Decision;

use function preg_match;
use function strval;
use function trim;

/**
 * Suggests the version label an upload form is pre-filled with. The label stays free-form and the uploader can always
 * overwrite it; version ordering never depends on it.
 */
final class VersionLabelSuggester
{
    public function suggest(?string $previousLabel): string
    {
        $previousLabel = null === $previousLabel
            ? ''
            : trim($previousLabel);

        if ('' === $previousLabel) {
            return 'v1.0';
        }

        if (
            1 === preg_match(
                '/^(v?)(\d+)\.(\d+)$/i',
                $previousLabel,
                $matches,
            )
        ) {
            return $matches[1] . $matches[2] . '.' . strval((int) $matches[3] + 1);
        }

        return $previousLabel;
    }
}
