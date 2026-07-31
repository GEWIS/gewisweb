<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Decision;
use App\Entity\Decision\MeetingPoint;
use App\ViewModel\Decision\DecisionMatchResult;

use function preg_match;
use function strval;
use function trim;

/**
 * Matches synced decisions to locally-managed agenda points at render time. Nothing is ever stored: agenda points can
 * shift during the actual meeting, and the board corrects a mismatch afterwards by renumbering the points, which
 * instantly re-attributes the decisions.
 *
 * A decision's point matches the agenda point whose free-form number has the same leading integer. An exact match
 * ("7") beats lettered variants ("7a"); when only lettered variants exist, the first by display order wins. Decisions
 * matching no agenda point are reported separately, so they are never shown on a wrong item.
 */
final class MeetingPointDecisionMatcher
{
    /**
     * @param list<MeetingPoint> $points    ordered by display position
     * @param list<Decision>     $decisions
     */
    public function match(
        array $points,
        array $decisions,
    ): DecisionMatchResult {
        $pointsByLeadingInteger = [];
        foreach ($points as $point) {
            if (
                1 !== preg_match(
                    '/^\s*(\d+)/',
                    $point->getNumber(),
                    $matches,
                )
            ) {
                continue;
            }

            $integer = (int) $matches[1];
            $exact = trim($point->getNumber()) === strval($integer);

            if (
                isset($pointsByLeadingInteger[$integer])
                && (
                    !$exact
                    || $pointsByLeadingInteger[$integer]['exact']
                )
            ) {
                continue;
            }

            $pointsByLeadingInteger[$integer] = [
                'point' => $point,
                'exact' => $exact,
            ];
        }

        $byPointId = [];
        $unmatched = [];
        foreach ($decisions as $decision) {
            $point = $pointsByLeadingInteger[$decision->getPoint()]['point'] ?? null;

            if (null === $point) {
                $unmatched[] = $decision;
                continue;
            }

            $byPointId[(int) $point->getId()][] = $decision;
        }

        return new DecisionMatchResult(
            $byPointId,
            $unmatched,
        );
    }
}
