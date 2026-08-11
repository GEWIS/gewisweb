<?php

declare(strict_types=1);

namespace App\ViewModel\Decision;

use App\Entity\Decision\Organ;

use function count;

/**
 * One of the bodies that has gone by an abbreviation, for the control that switches between them. An abbreviation is
 * reused: a committee is abrogated and years later another is founded under the same letters, and each has a page of
 * its own that the other should be reachable from.
 *
 * A body is addressed by the year it was founded, which reads well and is almost always enough. Two bodies founded in
 * the same year are addressed by their whole founding date instead, so neither is ever unreachable; {@see $key} is
 * whichever of the two applies, and {@see $sharesItsYear} says which happened so the reader is told them apart too.
 *
 * The list is only worth showing when there is more than one, which is what {@see self::fromOrgans()} answers with an
 * empty list for.
 */
final readonly class BodyIteration
{
    public function __construct(
        public string $key,
        public int $year,
        public ?int $abrogatedIn,
        public bool $sharesItsYear,
        public bool $current,
    ) {
    }

    /**
     * @param Organ[] $iterations newest first
     *
     * @return list<self>
     */
    public static function fromOrgans(
        array $iterations,
        Organ $shown,
    ): array {
        if (count($iterations) < 2) {
            return [];
        }

        $perYear = [];
        foreach ($iterations as $organ) {
            $year = $organ->getFoundationDate()->format('Y');
            $perYear[$year] = ($perYear[$year] ?? 0) + 1;
        }

        $rows = [];

        foreach ($iterations as $organ) {
            $founded = $organ->getFoundationDate();
            $year = $founded->format('Y');
            $shared = ($perYear[$year] ?? 0) > 1;
            $abrogation = $organ->getAbrogationDate();

            $rows[] = new self(
                $shared ? $founded->format('Y-m-d') : $year,
                (int) $year,
                null === $abrogation ? null : (int) $abrogation->format('Y'),
                $shared,
                $organ->getId() === $shown->getId(),
            );
        }

        return $rows;
    }
}
