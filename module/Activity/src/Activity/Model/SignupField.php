<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * SignupField model.
 *
 * @ORM\Entity
 */
class SignupField
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
     * Activity that the SignupField belongs to.
     *
     * @ORM\ManyToOne(targetEntity="Activity\Model\SignupList", inversedBy="fields", cascade={"persist"})
     * @ORM\JoinColumn(name="signupList_id",referencedColumnName="id")
     */
    protected $signupList;

    /**
     * The name of the SignupField.
     *
     * @ORM\OneToOne(targetEntity="Activity\Model\LocalisedText", orphanRemoval=true, cascade={"persist"})
     */
    protected $name;

    /**
     * The type of the SignupField.
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $type;

    /**
     * The minimal value constraint for the ``number'' type
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $minimumValue;

    /**
     * The maximal value constraint for the ``number'' type.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $maximumValue;

    /**
     * The allowed options for the SignupField of the ``option'' type.
     *
     * @ORM\OneToMany(targetEntity="Activity\Model\SignupOption", mappedBy="field")
     */
    protected $options;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Activity\Model\SignupList
     */
    public function getSignupList()
    {
        return $this->signupList;
    }

    /**
     * @param \Activity\Model\SignupList $signupList
     */
    public function setSignupList($signupList)
    {
        $this->signupList = $signupList;
    }

    /**
     * @return \Activity\Model\LocalisedText
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Activity\Model\LocalisedText $name
     */
    public function setName($name)
    {
        $this->name = $name->copy();
    }

    /**
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param integer $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return integer
     */
    public function getMinimumValue()
    {
        return $this->minimumValue;
    }

    /**
     * @param integer $minimumValue
     */
    public function setMinimumValue($minimumValue)
    {
        $this->minimumValue = $minimumValue;
    }

    /**
     * @return integer
     */
    public function getMaximumValue()
    {
        return $this->maximumValue;
    }

    /**
     * @param integer $maximumValue
     */
    public function setMaximumValue($maximumValue)
    {
        $this->maximumValue = $maximumValue;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];
        foreach ($this->getOptions() as $option) {
            $options[] = $option->toArray();
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'minimumValue' => $this->getMinimumValue(),
            'maximumValue' => $this->getMaximumValue(),
            'options' => $options
        ];
    }
}
