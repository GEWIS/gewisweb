<?php

namespace Activity\Model;


/**
 * Tranlated version of the ActivityOption model. Populate with values from an
 * instance of Activity\Model\ActivityOption, to only expose one language.
 * This model should NOT be preserved in the database.
 */
class ActivityOptionTranslation
{
    /**
     * ID for the field.
     */
    protected $id;

    /**
     * Field that the option belongs to.
     */
    protected $field;

    /**
     * The value of the option.
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
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getId()
    {
        return $this->id;
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

