<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Member;
use App\Entity\Decision\Organ;
use App\Entity\Decision\OrganMember;
use App\Repository\Decision\MemberRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_values;

/**
 * @psalm-type OrganMembershipType = array{
 *     organ: Organ,
 *     functions: list<string>,
 * }
 */
class MemberInfoService
{
    public function __construct(
        private readonly MemberRepository $memberRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Group a member's organ installations into the organs they are currently part of and those they used to be part
     * of, each with the functions (other than plain membership) they held there.
     *
     * @return array{
     *     current: list<OrganMembershipType>,
     *     historical: list<OrganMembershipType>,
     * }
     */
    public function getOrganMemberships(Member $member): array
    {
        return [
            'current' => $this->groupByOrgan($this->memberRepository->findCurrentInstallations($member)),
            'historical' => $this->groupByOrgan($this->memberRepository->findHistoricalInstallations($member)),
        ];
    }

    /**
     * @param OrganMember[] $installations
     *
     * @return list<OrganMembershipType>
     */
    private function groupByOrgan(array $installations): array
    {
        $organs = [];

        foreach ($installations as $installation) {
            $organ = $installation->getOrgan();
            $abbreviation = $organ->getAbbr();

            if (!isset($organs[$abbreviation])) {
                $organs[$abbreviation] = [
                    'organ' => $organ,
                    'functions' => [],
                ];
            }

            $function = $installation->getFunction();
            if ($function->isAdministrative()) {
                continue;
            }

            $organs[$abbreviation]['functions'][] = $function->getName($this->translator);
        }

        return array_values($organs);
    }
}
