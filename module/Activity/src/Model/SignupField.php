<?php

namespace Activity\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
    OneToMany,
    OneToOne,
};

/**
 * SignupField model.
 */
#[Entity]
class SignupField
{
    /**
     * ID for the field.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected ?int $id = null;

    /**
     * Activity that the SignupField belongs to.
     */
    #[ManyToOne(
        targetEntity: SignupList::class,
        cascade: ["persist"],
        inversedBy: "fields",
    )]
    #[JoinColumn(
        name: "signuplist_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected SignupList $signupList;

    /**
     * The name of the SignupField.
     */
    #[OneToOne(
        targetEntity: LocalisedText::class,
        cascade: ["persist"],
        orphanRemoval: true,
    )]
    protected LocalisedText $name;

    /**
     * The type of the SignupField.
     */
    #[Column(type: "integer")]
    protected int $type;

    /**
     * The minimal value constraint for the ``number'' type.
     */
    #[Column(
        type: "integer",
        nullable: true,
    )]
    protected ?int $minimumValue = null;

    /**
     * The maximal value constraint for the ``number'' type.
     */
    #[Column(
        type: "integer",
        nullable: true,
    )]
    protected ?int $maximumValue = null;

    /**
     * The allowed options for the SignupField of the ``option'' type.
     */
    #[OneToMany(
        targetEntity: SignupOption::class,
        mappedBy: "field",
        orphanRemoval: true,
    )]
    protected Collection $options;

    public function __construct() {
        $this->options = new ArrayCollection();
    }

    /**
     * @return SignupList
     */
    public function getSignupList(): SignupList
    {
        return $this->signupList;
    }

    /**
     * @param SignupList $signupList
     */
    public function setSignupList(SignupList $signupList): void
    {
        $this->signupList = $signupList;
    }

    /**
     * @return Collection
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return LocalisedText
     */
    public function getName(): LocalisedText
    {
        return $this->name;
    }

    /**
     * @param LocalisedText $name
     */
    public function setName(LocalisedText $name): void
    {
        $this->name = $name->copy();
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return int|null
     */
    public function getMinimumValue(): ?int
    {
        return $this->minimumValue;
    }

    /**
     * @param int|null $minimumValue
     */
    public function setMinimumValue(?int $minimumValue): void
    {
        $this->minimumValue = $minimumValue;
    }

    /**
     * @return int|null
     */
    public function getMaximumValue(): ?int
    {
        return $this->maximumValue;
    }

    /**
     * @param int|null $maximumValue
     */
    public function setMaximumValue(?int $maximumValue): void
    {
        $this->maximumValue = $maximumValue;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return array
     */
    public function toArray(): array
    {
        $options = [];
        $optionsEn = [];

        foreach ($this->getOptions() as $option) {
            $optionData = $option->toArray();
            $options[] = $optionData['value'];
            $optionsEn[] = $optionData['valueEn'];
        }

        return [
            'id' => $this->getId(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'type' => $this->getType(),
            'minimumValue' => $this->getMinimumValue(),
            'maximumValue' => $this->getMaximumValue(),
            'options' => $options,
            'optionsEn' => $optionsEn,
        ];
    }
}
