<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Decision\Organ;
use App\Repository\Activity\ProposalLimitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * A standing exception to how many activities one body may propose per option period.
 *
 * Rows only exist where the board wants a different number than usual; every other body is answered by the ladder in
 * {@see \App\Service\Activity\ProposalLimitResolver} without a row of its own. That is the whole point: the previous
 * design wrote a row per body when a period was opened and defaulted them all to zero, so a body founded afterwards
 * had no row, resolved to zero, and could not take part at all.
 *
 * Standing rather than per period because the bodies that need an exception (first-year committees, say) need the same
 * one every quartile, and an exception the board has to remember to re-enter is an exception they will forget. A
 * single period can still be treated differently through {@see PeriodProposalLimit}.
 */
#[Entity(repositoryClass: ProposalLimitRepository::class)]
#[UniqueConstraint(
    name: 'proposal_limit_organ_uniq',
    columns: ['organ_id'],
)]
#[UniqueEntity(
    fields: ['organ'],
    message: 'This body already has a standing limit.',
)]
class ProposalLimit
{
    use IdentifiableTrait;

    #[ManyToOne(targetEntity: Organ::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organ $organ;

    /**
     * How many activities this body may propose in a period. Zero is a real answer here, and the only way a body ends
     * up unable to propose anything: the board has to write it down deliberately.
     */
    #[Column(type: Types::INTEGER)]
    private int $maxProposals;

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
