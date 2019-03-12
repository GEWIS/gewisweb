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
     * @ORM\ManyToOne(targetEntity="ActivityField", inversedBy="options", cascade={"persist"})
     * @ORM\JoinColumn(name="field_id",referencedColumnName="id")
     */
    protected $field;

    /**
     * The value of the option.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true)
     */
    protected $value;

    /**
     * Set the field the option belongs to.
     *
     * @param Activity\Model\ActivityField $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * Set the value of the option.
     *
     * @param LocalisedText $value
     */
    public function setValue($value)
    {
        $this->value = $value->copy();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getField()
    {
        return $this->field;
    }

    /**
     * @return LocalisedText
     */
    public function getValue()
    {
        return $this->value;
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
            'value' => $this->getValue()
        ];
    }
}
