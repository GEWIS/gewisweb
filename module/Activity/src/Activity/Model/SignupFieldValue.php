<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * SignupFieldValue model.
 *
 * @ORM\Entity
 */
class SignupFieldValue
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
     * @ORM\ManyToOne(targetEntity="SignupField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id")
     */
    protected $field;

    /**
     * Signup which the value belongs to.
     *
     * @ORM\ManyToOne(targetEntity="Signup", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="signup_id", referencedColumnName="id")
     */
    protected $signup;

    /**
     * The value of the assoctiated field, is not an option.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $value;

    /**
     * The option chosen.
     *
     * @ORM\ManyToOne(targetEntity="SignupOption")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id")
     */
    protected $option;

    /**
     * @return \Activity\Model\SignupField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set the field.
     *
     * @param \Activity\Model\SignupField $field
     */
    public function setField(SignupField $field)
    {
        $this->field = $field;
    }

    /**
     * Set the signup.
     *
     * @param \Activity\Model\Signup $signup
     */
    public function setSignup(Signup $signup)
    {
        $this->signup = $signup;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
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

    /**
     * @return \Activity\Model\SignupOption
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param \Activity\Model\SignupOption
     */
    public function setOption($option)
    {
        $this->option = $option;
    }
}
