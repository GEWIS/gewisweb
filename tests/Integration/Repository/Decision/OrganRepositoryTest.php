<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository\Decision;

use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Organ;
use App\Repository\Decision\OrganRepository;
use App\Tests\Integration\DatabaseTestCase;

use function array_map;
use function count;
use function rsort;

/**
 * An abbreviation belongs to whichever body currently holds it, and to every body that held it before. Reading only
 * the newest one is what made the older ones unreachable, so this pins that all of them come back and in which order.
 */
final class OrganRepositoryTest extends DatabaseTestCase
{
    public function testEveryBodyThatHeldAnAbbreviationComesBackNewestFirst(): void
    {
        $iterations = $this->repository()->findAllByAbbr(
            'GETÉST',
            OrganTypes::Committee,
        );

        self::assertGreaterThan(
            1,
            count($iterations),
            'The seed is expected to contain a reused abbreviation.',
        );

        $years = array_map(
            static fn (Organ $organ): int => (int) $organ->getFoundationDate()->format('Y'),
            $iterations,
        );

        $sorted = $years;
        rsort($sorted);

        self::assertSame(
            $sorted,
            $years,
        );
    }

    /**
     * The type is part of what an abbreviation means, so asking for the wrong one finds nothing rather than the body
     * of another kind that happens to share the letters.
     */
    public function testABodyOfAnotherKindDoesNotAnswer(): void
    {
        self::assertSame(
            [],
            $this->repository()->findAllByAbbr(
                'GETÉST',
                OrganTypes::Fraternity,
            ),
        );
    }

    /**
     * The overviews narrow by type, which used to fail outright because the enum was named as the parameter's column
     * type.
     */
    public function testTheOverviewsCanNarrowByType(): void
    {
        self::assertNotEmpty($this->repository()->findActive(OrganTypes::Committee));
        self::assertNotEmpty($this->repository()->findAbrogated(OrganTypes::Committee));
    }

    private function repository(): OrganRepository
    {
        return self::getContainer()->get(OrganRepository::class);
    }
}
