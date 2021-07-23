<?php

namespace Activity\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * SignupFieldValue model.
 */
#[Entity]
class SignupFieldValue
{
    /**
     * ID for the field value.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * Field which the value belongs to.
     */
    #[ManyToOne(targetEntity: "Activity\Model\SignupField")]
    #[JoinColumn(
        name: "field_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected SignupField $field;

    /**
     * Signup which the value belongs to.
     */
    #[ManyToOne(
        targetEntity: "Activity\Model\Signup",
        inversedBy: "fieldValues",
    )]
    #[JoinColumn(
        name: "signup_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected Signup $signup;

    /**
     * The value of the associated field, is not an option.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $value;

    /**
     * The option chosen.
     *
     * @ORM\ManyToOne(targetEntity="SignupOption")
     * @ORM\JoinColumn(name="option_id", referencedColumnName="id")
     */
    #[ManyToOne(targetEntity: "Activity\Model\SignupOption")]
    #[JoinColumn(
        name: "option_id",
        referencedColumnName: "id",
    )]
    protected ?SignupOption $option;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return SignupField
     */
    public function getField(): SignupField
    {
        return $this->field;
    }

    /**
     * Set the field.
     *
     * @param SignupField $field
     */
    public function setField(SignupField $field): void
    {
        $this->field = $field;
    }

    /**
     * @return Signup
     */
    public function getSignup(): Signup
    {
        return $this->signup;
    }

    /**
     * Set the signup.
     *
     * @param Signup $signup
     */
    public function setSignup(Signup $signup): void
    {
        $this->signup = $signup;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the value.
     *
     * @param string|null $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return SignupOption|null
     */
    public function getOption(): ?SignupOption
    {
        return $this->option;
    }

    /**
     * @param SignupOption|null $option
     */
    public function setOption(?SignupOption $option): void
    {
        $this->option = $option;
    }
}
