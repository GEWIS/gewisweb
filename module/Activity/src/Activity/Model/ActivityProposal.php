<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;
use Activity\Model\Activity;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class ActivityProposal 
{

    /**
     * ID for the proposal
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * The previous activity version, if any.
     * 
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $old;

    /**
     * The new activity
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity")
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $new;


    public function getOld() {
        return $this->old;
    }

    public function getNew() {
        return $this->new;
    }

    public function setOld($old) {
        $this->parent = $old;
    }

    public function setNew($new) {
        $this->new = $new;
    }


}
