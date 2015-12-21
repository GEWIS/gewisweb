<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Activity model.
 *
 * @ORM\Entity
 */
class ActivityFieldValue
{
    /**
     * ID for the field value.
     *
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * Field which the value belongs to.
     * 
     * @ORM\ManyToOne(targetEntity="ActivityField")
     * @ORM\JoinColumn(name="field_id",referencedColumnName="id")
     */
    protected $field;

    /**
     * Signup which the value belongs to.
     *
     * @ORM\ManyToOne(targetEntity="ActivitySignup")
     * @ORM\JoinColumn(name="signup_id", referencedColumnName="id")
     */
    protected $signup;

    /**
     * The value of the assoctiated field.
     * 
     * @ORM\Column(type="string")
     */
    protected $value;
    
    /**
     * Get the status of a variable
     *
     * @param $variable
     * @return mixed
     */
    public function get($variable)
    {
        return $this->$variable;
    }
    
    /**
     * Set the field.
     * 
     * @param \Activity\Model\Activity\Model\ActivityField $field
     */
    public function setField(\Activity\Model\ActivityField $field)
    {
        $this->field = $field;
    }
    
    /**
     * Set the signup.
     * 
     * @param \Activity\Model\Activity\Model\ActivitySignup $signup
     */
    public function setSignup(\Activity\Model\ActivitySignup $signup)
    {
        $this->signup = $signup;
    }
    
    /**
     * Set the value.
     * 
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
