<?php

declare(strict_types=1);

namespace App\Service\Decision;

use DateTimeImmutable;

use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function str_contains;
use function strval;
use function trim;

/**
 * Best-effort interpretation of the legacy flat document names, which embedded the agenda point ("AV Stuk 105.3.1 -",
 * "2.1 Agenda"), the version ("(v1.2)"), and sometimes the upload date ("(03-06-2020)") in the name itself. Anything
 * unparseable stays a meeting-level document under its full original name; the migrator never guesses beyond these
 * conventions.
 */
final class LegacyDocumentNameParser
{
    /**
     * Numbers above this are years or meeting numbers that leaked into the name, never agenda points.
     */
    private const int MAX_POINT_NUMBER = 50;

    /**
     * Recurring documents that move to the reference library, matched on a normalised name fragment. Order matters:
     * the combined memorandum must match before its two halves.
     */
    private const array REFERENCE_FRAGMENTS = [
        'eternal memorandum and decision' => 'eternal-memorandum-and-decision-list',
        'eternal memorandum' => 'eternal-memorandum',
        'eternal decision' => 'eternal-decision-list',
        'scenario' => 'scenarios-and-procedures',
        'samenvattingen oude' => 'summaries-of-old-gmms',
        'summaries of old' => 'summaries-of-old-gmms',
        'financial definition' => 'financial-definition-list',
        'definition list' => 'financial-definition-list',
        'definitionlist' => 'financial-definition-list',
        'translation template' => 'translation-template-decision-list',
    ];

    public function parse(string $rawName): ParsedLegacyName
    {
        $name = trim($rawName);

        $versionLabel = null;
        $versionDate = null;

        // Strip trailing "(v1.2)" and "(03-06-2020)" in any order; some names carry both.
        while (true) {
            if (
                1 === preg_match(
                    '/\s*\((v[0-9][0-9a-z.\/]*)\)$/i',
                    $name,
                    $matches,
                )
            ) {
                $versionLabel ??= $matches[1];
                $name = trim(preg_replace(
                    '/\s*\((v[0-9][0-9a-z.\/]*)\)$/i',
                    '',
                    $name,
                ) ?? $name);
                continue;
            }

            if (
                1 === preg_match(
                    '/\s*\((\d{1,2}-\d{1,2}-\d{4})\)$/',
                    $name,
                    $matches,
                )
            ) {
                $date = DateTimeImmutable::createFromFormat(
                    '!d-m-Y',
                    $matches[1],
                );
                $versionDate ??= false === $date
                    ? null
                    : $date;
                $name = trim(preg_replace(
                    '/\s*\((\d{1,2}-\d{1,2}-\d{4})\)$/',
                    '',
                    $name,
                ) ?? $name);
                continue;
            }

            if (
                1 === preg_match(
                    '/\s*\((\d{4}-\d{1,2}-\d{1,2})\)$/',
                    $name,
                    $matches,
                )
            ) {
                $date = DateTimeImmutable::createFromFormat(
                    '!Y-m-d',
                    $matches[1],
                );
                $versionDate ??= false === $date
                    ? null
                    : $date;
                $name = trim(preg_replace(
                    '/\s*\((\d{4}-\d{1,2}-\d{1,2})\)$/',
                    '',
                    $name,
                ) ?? $name);
                continue;
            }

            break;
        }

        $pointNumber = null;
        $baseName = $name;

        if (
            1 === preg_match(
                '/^AV[\s-]*stuk\s+\d+\.(\d+)(?:\.\d+)?\s*[-–—:]?\s*(.+)$/iu',
                $name,
                $matches,
            )
        ) {
            $pointNumber = strval((int) $matches[1]);
            $baseName = trim($matches[2]);
        } elseif (
            1 === preg_match(
                '/^(\d+)(?:\.\d+)*[.\s:]\s*(.+)$/u',
                $name,
                $matches,
            )
            && (int) $matches[1] <= self::MAX_POINT_NUMBER
        ) {
            $pointNumber = strval((int) $matches[1]);
            $baseName = trim($matches[2]);
        }

        $groupKey = mb_strtolower(trim(preg_replace(
            '/\s+/u',
            ' ',
            $baseName,
        ) ?? $baseName));

        $referenceKey = null;
        foreach (self::REFERENCE_FRAGMENTS as $fragment => $key) {
            if (
                str_contains(
                    $groupKey,
                    $fragment,
                )
            ) {
                $referenceKey = $key;
                break;
            }
        }

        return new ParsedLegacyName(
            $pointNumber,
            '' === $baseName ? trim($rawName) : $baseName,
            $groupKey,
            $versionLabel,
            $versionDate,
            $referenceKey,
        );
    }
}
