<?php

declare(strict_types=1);

namespace App\Tests\ViewModel\Decision;

use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Organ;
use App\ViewModel\Decision\BodyIteration;
use DateTime;
use PHPUnit\Framework\TestCase;

use function array_map;
use function str_replace;

/**
 * An abbreviation is reused: a committee is abrogated and years later another is founded under the same letters. Both
 * have a page, and each has to be reachable from the other, which is what this list is for.
 */
final class BodyIterationTest extends TestCase
{
    /**
     * One body under an abbreviation needs no switcher, so there is nothing to show and the page says nothing about
     * other bodies by that name.
     */
    public function testOneBodyUnderAnAbbreviationIsNotWorthListing(): void
    {
        $organ = $this->organ(
            2026,
            null,
        );

        self::assertSame(
            [],
            BodyIteration::fromOrgans(
                [$organ],
                $organ,
            ),
        );
    }

    public function testEveryBodyUnderTheAbbreviationIsListedWithItsYears(): void
    {
        $current = $this->organ(
            2026,
            null,
        );
        $former = $this->organ(
            2017,
            2020,
        );

        $iterations = BodyIteration::fromOrgans(
            [
                $current,
                $former,
            ],
            $current,
        );

        self::assertSame(
            [
                2026,
                2017,
            ],
            array_map(
                static fn (BodyIteration $iteration): int => $iteration->year,
                $iterations,
            ),
        );
        self::assertSame(
            [
                null,
                2020,
            ],
            array_map(
                static fn (BodyIteration $iteration): ?int => $iteration->abrogatedIn,
                $iterations,
            ),
        );
    }

    /**
     * A year is enough to say which body is meant, until two of them were founded in the same one. Then the whole
     * founding date is what tells them apart, and neither may end up unreachable.
     */
    public function testTwoBodiesFoundedInTheSameYearAreAddressedByTheirWholeDate(): void
    {
        $spring = $this->organ(
            2017,
            2017,
            '03-08',
        );
        $autumn = $this->organ(
            2017,
            null,
            '11-14',
        );

        $iterations = BodyIteration::fromOrgans(
            [
                $autumn,
                $spring,
            ],
            $autumn,
        );

        self::assertSame(
            [
                '2017-11-14',
                '2017-03-08',
            ],
            array_map(
                static fn (BodyIteration $iteration): string => $iteration->key,
                $iterations,
            ),
        );
        self::assertSame(
            [
                true,
                true,
            ],
            array_map(
                static fn (BodyIteration $iteration): bool => $iteration->sharesItsYear,
                $iterations,
            ),
        );
    }

    /**
     * A year of its own stays the address, since it reads better than a date and is all that is needed.
     */
    public function testABodyWhoseYearIsItsOwnIsAddressedByThatYear(): void
    {
        $current = $this->organ(
            2026,
            null,
        );
        $former = $this->organ(
            2017,
            2020,
        );

        self::assertSame(
            [
                '2026',
                '2017',
            ],
            array_map(
                static fn (BodyIteration $iteration): string => $iteration->key,
                BodyIteration::fromOrgans(
                    [
                        $current,
                        $former,
                    ],
                    $current,
                ),
            ),
        );
    }

    /**
     * Exactly one of them is the body being read, which is what the page marks rather than links.
     */
    public function testOnlyTheBodyBeingReadIsMarkedAsCurrent(): void
    {
        $current = $this->organ(
            2026,
            null,
        );
        $former = $this->organ(
            2017,
            2020,
        );

        $iterations = BodyIteration::fromOrgans(
            [
                $current,
                $former,
            ],
            $former,
        );

        self::assertSame(
            [
                false,
                true,
            ],
            array_map(
                static fn (BodyIteration $iteration): bool => $iteration->current,
                $iterations,
            ),
        );
    }

    private function organ(
        int $foundedIn,
        ?int $abrogatedIn,
        string $foundedOn = '05-19',
    ): Organ {
        $organ = new Organ();
        $organ->setAbbr('GETÉST');
        $organ->setName('A committee');
        $organ->setType(OrganTypes::Committee);
        $organ->setFoundationDate(new DateTime($foundedIn . '-' . $foundedOn));

        if (null !== $abrogatedIn) {
            $organ->setAbrogationDate(new DateTime($abrogatedIn . '-12-31'));
        }

        // Two bodies are told apart by id, which a fresh entity has none of; the seed is what gives them one, so the
        // test does the same by hand.
        $organ->setId((int) ($foundedIn . str_replace('-', '', $foundedOn)));

        return $organ;
    }
}
