<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * SignupOption model.
 * Contains the possible options of a field of type ``option''.
 *
 * @ORM\Entity
 */
class SignupOption
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
     * @ORM\ManyToOne(targetEntity="Activity\Model\SignupField", inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="field_id",referencedColumnName="id")
     */
    protected $field;

    /**
     * The value of the option.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist"})
     */
    protected $value;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Activity\Model\SignupField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set the field the option belongs to.
     *
     * @param \Activity\Model\SignupField $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return \Activity\Model\LocalisedText
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of the option.
     *
     * @param \Activity\Model\LocalisedText $value
     */
    public function setValue($value)
    {
        $this->value = $value->copy();
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'value' => $this->getValue()->getValueNL(),
            'valueEn' => $this->getValue()->getValueEN(),
        ];
    }
}
