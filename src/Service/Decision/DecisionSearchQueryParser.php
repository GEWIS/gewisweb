<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use InvalidArgumentException;

use function implode;
use function mb_substr;
use function preg_match;
use function preg_match_all;
use function str_starts_with;
use function strtoupper;
use function trim;

/**
 * Interprets a decision search prompt: bare words must all appear in the text, `"quoted phrases"` match as a whole,
 * a leading `-` excludes a word or phrase, and `type:bm` (any meeting abbreviation) restricts the meeting type.
 * Everything that is not an operator is kept verbatim for the meeting-reference match ("GMM 214.3.1").
 */
final class DecisionSearchQueryParser
{
    public function parse(string $query): DecisionSearchQuery
    {
        $includeTerms = [];
        $excludeTerms = [];
        $type = null;
        $remainderParts = [];

        preg_match_all(
            '/-?"[^"]*"|\S+/u',
            $query,
            $matches,
        );

        foreach ($matches[0] as $token) {
            $negated = str_starts_with(
                $token,
                '-',
            ) && '-' !== $token;
            $body = $negated ? mb_substr(
                $token,
                1,
            ) : $token;

            if (
                str_starts_with(
                    $body,
                    '"',
                )
            ) {
                $phrase = trim(trim(
                    $body,
                    '"',
                ));
                if ('' !== $phrase) {
                    if ($negated) {
                        $excludeTerms[] = $phrase;
                    } else {
                        $includeTerms[] = $phrase;
                    }
                }

                continue;
            }

            if (
                !$negated
                && 1 === preg_match(
                    '/^type:(\S+)$/iu',
                    $body,
                    $typeMatch,
                )
            ) {
                try {
                    $type = MeetingTypes::tryFromSearch(strtoupper($typeMatch[1]));
                    continue;
                } catch (InvalidArgumentException) {
                    // Not a meeting type; fall through and search for the token verbatim.
                }
            }

            if ($negated) {
                $excludeTerms[] = $body;
                continue;
            }

            $includeTerms[] = $body;
            $remainderParts[] = $token;
        }

        return new DecisionSearchQuery(
            $includeTerms,
            $excludeTerms,
            $type,
            implode(
                ' ',
                $remainderParts,
            ),
        );
    }
}
