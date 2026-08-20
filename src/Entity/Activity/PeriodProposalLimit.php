<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Organ;
use App\Repository\Activity\PeriodProposalLimitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * A one-off exception to how many activities one body may propose, for a single option period.
 *
 * Beats the body's standing {@see ProposalLimit} and the period's own default. Like the standing limit, rows exist
 * only where the board wants something different; nothing is generated when a period is opened.
 *
 * This is a separate entity rather than a nullable period on {@see ProposalLimit} because MariaDB treats NULLs as
 * distinct in a unique index, so a single `UNIQUE (organ_id, period_id)` would happily accept two standing rows for
 * the same body. Two tables, two plain constraints, no invariant that only holds if everybody remembers it.
 */
#[Entity(repositoryClass: PeriodProposalLimitRepository::class)]
#[UniqueConstraint(
    name: 'period_proposal_limit_period_organ_uniq',
    columns: [
        'period_id',
        'organ_id',
    ],
)]
#[UniqueEntity(
    fields: [
        'period',
        'organ',
    ],
    message: 'This body already has a limit for this period.',
)]
class PeriodProposalLimit
{
    use IdentifiableTrait;

    #[ManyToOne(targetEntity: OptionPeriod::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private OptionPeriod $period;

    #[ManyToOne(targetEntity: Organ::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organ $organ;

    /**
     * How many activities this body may propose in this period. Zero is a real answer, deliberately written down.
     */
    #[Column(type: Types::INTEGER)]
    private int $maxProposals;

    public function getPeriod(): OptionPeriod
    {
        return $this->period;
    }

    public function setPeriod(OptionPeriod $period): void
    {
        $this->period = $period;
    }

    public function getOrgan(): Organ
    {
        return $this->organ;
    }

    public function setOrgan(Organ $organ): void
    {
        $this->organ = $organ;
    }

    public function getMaxProposals(): int
    {
        return $this->maxProposals;
    }

    public function setMaxProposals(int $maxProposals): void
    {
        $this->maxProposals = $maxProposals;
    }
}
