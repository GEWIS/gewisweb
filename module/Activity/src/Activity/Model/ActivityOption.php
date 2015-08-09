<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity option model. 
 * Contains the possible options of a field of type ``option''.
 *
 * @ORM\Entity
 */
class ActivityOption
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
     * Field that the option belongs to.
     *
     * @ORM\ManyToOne(targetEntity="ActivityField", inversedBy="options")
     * @ORM\JoinColumn(name="field_id",referencedColumnName="id")
     */
    protected $field;
    
    /**
     * The value of the option.
     * 
     * @ORM\Column(type="string", nullable=false) 
     */
    protected $value;
  
    /**
     * The index of the field to determine the display order.
     * 
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $position;
    
    /**
     * Set the field the option belongs to.
     * 
     * @param Activity\Model\ActivityField $field
     */
    public function setField($field){
        
        $this->field = $field;
    }
    
    /**
     * Set the value of the option.
     * 
     * @param string $value
     */
    public function setValue($value){
        
        $this->value = $value;
    }
    
    public function get($variable)
    {
        return $this->$variable;
    }
}
