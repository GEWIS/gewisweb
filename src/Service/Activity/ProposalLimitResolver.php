<?php

declare(strict_types=1);

namespace App\Service\Activity;

use App\Entity\Activity\ActivityProposal;
use App\Entity\Activity\Enums\ProposalLimitSource;
use App\Entity\Activity\OptionPeriod;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ActivityProposalRepository;
use App\Repository\Activity\PeriodProposalLimitRepository;
use App\Repository\Activity\ProposalLimitRepository;
use App\ViewModel\Activity\ProposalAllowance;

/**
 * Works out how many activities a body may put forward in an option period, and how many of those it has used.
 *
 * A ladder from the most specific rule to the least: an exception the board wrote for this body in this period, then
 * one it wrote for this body full stop, then the number the board set for this period, then the number every body
 * gets. Nothing is ever written down in advance, so a rule that was never written simply does not match and the next
 * rung answers. That is the whole difference from the calendar this replaces, which wrote a row per body when a period
 * was opened, started every one of them at zero, and read a missing row as zero as well, so a body founded after the
 * period opened was silently shut out. Here a body with no rows anywhere gets the ordinary number, and zero is only
 * ever reached by the board writing it down on purpose.
 *
 * Deciding who may act for which body, and whether the period is even open, is left to the callers.
 */
final readonly class ProposalLimitResolver
{
    public function __construct(
        private PeriodProposalLimitRepository $periodLimitRepository,
        private ProposalLimitRepository $limitRepository,
        private ActivityProposalRepository $proposalRepository,
        private int $defaultMaxProposals,
    ) {
    }

    /**
     * The allowance of one body in one period.
     *
     * A proposal may be left out of the count, which is what an edit needs: a body on its last slot must still be able
     * to save a change to the proposal that used it.
     */
    public function allowanceFor(
        Organ $organ,
        OptionPeriod $period,
        ?ActivityProposal $excluding = null,
    ): ProposalAllowance {
        return $this->decide(
            $this->periodLimitRepository->findForPeriodAndOrgan(
                $period,
                $organ,
            )?->getMaxProposals(),
            $this->limitRepository->findForOrgan($organ)?->getMaxProposals(),
            $period,
            $this->proposalRepository->countForPeriodAndOrgan(
                $period,
                $organ,
                $excluding,
            ),
        );
    }

    /**
     * The same for a set of bodies, in a fixed number of queries however many bodies there are. The body picker lists
     * every body somebody may act for, so this must not grow with that list.
     *
     * @param Organ[] $organs
     *
     * @return array<int, ProposalAllowance> keyed by body
     */
    public function allowancesFor(
        array $organs,
        OptionPeriod $period,
    ): array {
        $periodLimits = $this->periodLimitRepository->findForPeriodAndOrgans(
            $period,
            $organs,
        );
        $standingLimits = $this->limitRepository->findForOrgans($organs);
        $used = $this->proposalRepository->countPerOrganForPeriod(
            $period,
            $organs,
        );

        $allowances = [];
        foreach ($organs as $organ) {
            $organId = $organ->getId();

            if (null === $organId) {
                continue;
            }

            $allowances[$organId] = $this->decide(
                ($periodLimits[$organId] ?? null)?->getMaxProposals(),
                ($standingLimits[$organId] ?? null)?->getMaxProposals(),
                $period,
                $used[$organId] ?? 0,
            );
        }

        return $allowances;
    }

    /**
     * The ladder itself, in one place, so the single-body answer and the batch answer cannot drift apart.
     */
    private function decide(
        ?int $periodOverride,
        ?int $standingOverride,
        OptionPeriod $period,
        int $used,
    ): ProposalAllowance {
        if (null !== $periodOverride) {
            return new ProposalAllowance(
                $periodOverride,
                $used,
                ProposalLimitSource::PeriodOverride,
            );
        }

        if (null !== $standingOverride) {
            return new ProposalAllowance(
                $standingOverride,
                $used,
                ProposalLimitSource::StandingOverride,
            );
        }

        $periodDefault = $period->getDefaultMaxProposals();

        if (null !== $periodDefault) {
            return new ProposalAllowance(
                $periodDefault,
                $used,
                ProposalLimitSource::PeriodDefault,
            );
        }

        return new ProposalAllowance(
            $this->defaultMaxProposals,
            $used,
            ProposalLimitSource::GlobalDefault,
        );
    }
}
