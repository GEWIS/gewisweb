<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Enums\InstallationFunctions;
use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\ViewModel\Decision\OrganMembers;
use App\ViewModel\Decision\OrganMembership;
use DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function array_search;
use function array_values;
use function min;
use function usort;

use const PHP_INT_MAX;

/**
 * Who is in a body, read off its installations rather than stored anywhere: an installation with no discharge, or one
 * whose discharge has not come round yet, is a current membership.
 *
 * Somebody installed as an inactive member is listed apart, and a member who is still in the body never shows up among
 * the former ones however many times they were installed and discharged before.
 */
final readonly class OrganMemberService
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * The functions worth listing first, in the order a body introduces itself in. Anything else is a function like any
     * other and keeps the order the installations came in.
     */
    private const array FUNCTION_ORDER = [
        InstallationFunctions::Chair,
        InstallationFunctions::Secretary,
        InstallationFunctions::Treasurer,
        InstallationFunctions::ViceChair,
    ];

    public function membersOf(Organ $organ): OrganMembers
    {
        $today = new DateTime();

        /** @var array<int, OrganMembership> $active */
        $active = [];
        /** @var array<int, list<InstallationFunctions>> $functions */
        $functions = [];
        /** @var array<int, Member> $inactive */
        $inactive = [];
        /** @var array<int, Member> $former */
        $former = [];

        foreach ($organ->getMembers() as $installation) {
            // An installation that has not taken effect yet says nothing about who is in the body today.
            if ($installation->getInstallDate() > $today) {
                continue;
            }

            $member = $installation->getMember();
            $lidnr = $member->getLidnr();
            $discharge = $installation->getDischargeDate();

            if (
                null !== $discharge
                && $discharge <= $today
            ) {
                $former[$lidnr] ??= $member;

                continue;
            }

            if (InstallationFunctions::InactiveMember === $installation->getFunction()) {
                $inactive[$lidnr] ??= $member;

                continue;
            }

            $membership = $active[$lidnr] ??= new OrganMembership($member);

            // Being a member is what everybody here is; only a function beyond that is worth naming.
            $function = $installation->getFunction();
            if ($function->isAdministrative()) {
                continue;
            }

            $functions[$lidnr][] = $function;
            $active[$lidnr] = $membership->withFunctions([
                ...$membership->functions,
                $function->getName($this->translator),
            ]);
        }

        // Somebody who came back is a current member, whatever an older discharge says.
        foreach ($former as $lidnr => $member) {
            if (
                !isset($active[$lidnr])
                && !isset($inactive[$lidnr])
            ) {
                continue;
            }

            unset($former[$lidnr]);
        }

        // Sorted on the functions themselves rather than on their names, which read differently per language.
        $ranked = [];
        foreach ($active as $lidnr => $membership) {
            $ranked[] = [
                $this->rank($functions[$lidnr] ?? []),
                $membership,
            ];
        }

        usort(
            $ranked,
            static fn (array $a, array $b): int => $a[0] <=> $b[0],
        );

        $active = array_map(
            static fn (array $row): OrganMembership => $row[1],
            $ranked,
        );

        return new OrganMembers(
            $active,
            array_values($inactive),
            array_values($former),
        );
    }

    /**
     * The chair first, then the secretary, the treasurer and the vice-chair; everybody else after them.
     *
     * @param list<InstallationFunctions> $functions
     */
    private function rank(array $functions): int
    {
        $ranks = array_map(
            static function (InstallationFunctions $function): int {
                $position = array_search(
                    $function,
                    self::FUNCTION_ORDER,
                    true,
                );

                return false === $position
                    ? PHP_INT_MAX
                    : $position;
            },
            $functions,
        );

        return [] === $ranks
            ? PHP_INT_MAX
            : min($ranks);
    }
}
