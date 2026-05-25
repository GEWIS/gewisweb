<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\ActivityUpdateProposalRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Update prop model.
 */
#[Entity(repositoryClass: ActivityUpdateProposalRepository::class)]
class ActivityUpdateProposal
{
    use IdentifiableTrait;

    /**
     * The previous activity version, if any.
     */
    #[ManyToOne(
        targetEntity: Activity::class,
        inversedBy: 'updateProposal',
    )]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Activity $old;

    /**
     * The new activity.
     */
    #[ManyToOne(targetEntity: Activity::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Activity $new;

    public function getOld(): Activity
    {
        return $this->old;
    }

    public function setOld(Activity $old): void
    {
        $this->old = $old;
    }

    public function getNew(): Activity
    {
        return $this->new;
    }

    public function setNew(Activity $new): void
    {
        $this->new = $new;
    }
}
