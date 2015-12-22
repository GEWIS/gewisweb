<?php

namespace Activity\Model;


/**
 * Tranlated version of the ActivityField model. Populate with values from an
 * instance of Activity\Model\ActivityField, to only expose one language.
 * This model should NOT be preserved in the database.
 */
class ActivityFieldTranslation
{
    /**
     * ID for the field.
     */
    protected $id;

    /**
     * Activity that the field belongs to.
     */
    protected $activity;

    /**
     * The name of the field.
     */
    protected $name;

    /**
     * The type of the field.
     */
    protected $type;

    /**
     * The minimal value constraint for the ``number'' type
     */
    protected $minimumValue;

    /**
     * The maximal value constraint for the ``number'' type.
     */
    protected $maximumValue;

    /**
     * The allowed options for the field of the ``option'' type.
     */
    protected $options;

    public function setActivity($activity)
    {
        $this->activity = $activity;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setMinimumValue($minimumValue)
    {
        $this->minimumValue = $minimumValue;
    }

    public function setMaximumValue($maximumValue)
    {
        $this->maximumValue = $maximumValue;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getMinimumValue()
    {
        return $this->minimumValue;
    }

    public function getMaximumValue()
    {
        return $this->maximumValue;
    }

    public function getOptions()
    {
        return $this->options;
    }
}
