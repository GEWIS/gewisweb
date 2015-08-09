<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity field model.
 *
 * @ORM\Entity
 */
class ActivityField
{
    /**
     * ID for the field.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    
    /**
     * Activity that the field belongs to.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\Activity", inversedBy="fields")
     * @ORM\JoinColumn(name="activity_id",referencedColumnName="id")
     */
    protected $activity;
    
    /**
     * The name of the field.
     * 
     * @ORM\Column(type="string", nullable=false) 
     */
    protected $name;

    /**
     * The type of the field.
     * 
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $type;
    
    /**
     * The minimal value constraint for the ``number'' type
     * 
     * @ORM\Column(type="integer")
     */
    protected $minValue;
    
    /**
     * The maximal value constraint for the ``number'' type.
     * 
     * @ORM\Column(type="integer")
     */
    protected $maxValue;
    
    /**
     * The allowed options for the field of the ``option'' type.
     * 
     * @ORM\OneToMany(targetEntity="ActivityOption", mappedBy="field")
     */
    protected $options;
    
    /**
     * The index of the field to determine the display order.
     * 
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $position;
    
    public function get($variable)
    {
        return $this->$variable;
    }
}