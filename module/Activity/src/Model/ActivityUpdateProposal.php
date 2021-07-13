<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Update prop model.
 *
 * @ORM\Entity
 */
class ActivityUpdateProposal
{
    /**
     * ID for the proposal.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The previous activity version, if any.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="updateProposal")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $old;

    /**
     * The new activity.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $new;

    public function getId()
    {
        return $this->id;
    }

    public function getOld()
    {
        return $this->old;
    }

    public function setOld(Activity $old)
    {
        $this->old = $old;
    }

    public function getNew()
    {
        return $this->new;
    }

    public function setNew(Activity $new)
    {
        $this->new = $new;
    }
}
