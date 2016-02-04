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
     * @ORM\ManyToOne(targetEntity="ActivitySignup", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="signup_id", referencedColumnName="id")
     */
    protected $signup;

    /**
     * The value of the assoctiated field, is not an option.
     *
     * @ORM\Column(type="string",nullable=true)
     */
    protected $value;

    /**
     * The option chosen.
     *
     * @ORM\ManyToOne(targetEntity="ActivityOption")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id")
     */
    protected $option;

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

    public function getOption()
    {
        return $this->option;
    }

    public function setOption($option)
    {
        $this->option = $option;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getValue()
    {
        return $this->value;
    }
}
