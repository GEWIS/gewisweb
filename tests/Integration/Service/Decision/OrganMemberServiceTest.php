<?php

declare(strict_types=1);

namespace App\Tests\Integration\Service\Decision;

use App\Entity\Decision\Organ;
use App\Repository\Decision\OrganRepository;
use App\Service\Decision\OrganMemberService;
use App\Tests\Integration\DatabaseTestCase;
use App\ViewModel\Decision\OrganMembership;

use function array_map;

/**
 * Who is in a body is not stored anywhere: it is read off the installations the decisions left behind. These pin the
 * reading, since a body's page and the members overview both show what comes out of it.
 */
final class OrganMemberServiceTest extends DatabaseTestCase
{
    public function testTheChairIsListedFirst(): void
    {
        $members = $this->service()->membersOf($this->organ('GETÉST'));

        self::assertNotEmpty($members->active);
        self::assertContains(
            'Chair',
            $members->active[0]->functions,
        );
    }

    /**
     * Being a member is what everybody in a body is, so it is not a function worth naming beside somebody's name.
     */
    public function testPlainMembershipIsNotNamedAsAFunction(): void
    {
        $members = $this->service()->membersOf($this->organ('GETÉST'));

        foreach ($members->active as $membership) {
            self::assertNotContains(
                'Member',
                $membership->functions,
            );
        }
    }

    /**
     * A body that was abrogated has nobody in it any more, so everybody it ever had is a former member.
     */
    public function testAnAbrogatedBodyHasOnlyFormerMembers(): void
    {
        $former = $this->service()->membersOf($this->organ(
            'GETÉST',
            true,
        ));

        self::assertSame(
            [],
            $former->active,
        );
        self::assertSame(
            [],
            $former->inactive,
        );
    }

    /**
     * Nobody is in two of the lists at once: a member who was discharged and installed again is a current member, not
     * a former one.
     */
    public function testTheListsDoNotOverlap(): void
    {
        $members = $this->service()->membersOf($this->organ('GETÉST'));

        $current = array_map(
            static fn (OrganMembership $membership): int => $membership->member->getLidnr(),
            $members->active,
        );

        foreach ($members->former as $member) {
            self::assertNotContains(
                $member->getLidnr(),
                $current,
            );
        }
    }

    private function service(): OrganMemberService
    {
        return self::getContainer()->get(OrganMemberService::class);
    }

    private function organ(
        string $abbr,
        bool $abrogated = false,
    ): Organ {
        foreach (self::getContainer()->get(OrganRepository::class)->findAllByAbbr($abbr) as $organ) {
            if ($organ->isAbrogated() !== $abrogated) {
                continue;
            }

            return $organ;
        }

        self::fail('The seed is expected to contain such a body.');
    }
}
