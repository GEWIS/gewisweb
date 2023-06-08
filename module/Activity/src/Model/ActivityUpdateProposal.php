<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Update prop model.
 */
#[Entity]
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
    protected Activity $old;

    /**
     * The new activity.
     */
    #[ManyToOne(targetEntity: Activity::class)]
    #[JoinColumn(
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected Activity $new;

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
