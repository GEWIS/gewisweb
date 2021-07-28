<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * Update prop model.
 */
#[Entity]
class ActivityUpdateProposal
{
    /**
     * ID for the proposal.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * The previous activity version, if any.
     */
    #[ManyToOne(
        targetEntity: Activity::class,
        inversedBy: "updateProposal",
    )]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Activity $old;

    /**
     * The new activity.
     */
    #[ManyToOne(targetEntity: Activity::class)]
    #[JoinColumn(
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Activity $new;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Activity
     */
    public function getOld(): Activity
    {
        return $this->old;
    }

    /**
     * @param Activity $old
     */
    public function setOld(Activity $old): void
    {
        $this->old = $old;
    }

    /**
     * @return Activity
     */
    public function getNew(): Activity
    {
        return $this->new;
    }

    /**
     * @param Activity $new
     */
    public function setNew(Activity $new): void
    {
        $this->new = $new;
    }
}
