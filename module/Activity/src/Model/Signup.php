<?php

namespace Activity\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{
    Column,
    DiscriminatorColumn,
    DiscriminatorMap,
    Entity,
    GeneratedValue,
    Id,
    InheritanceType,
    JoinColumn,
    ManyToOne,
    OneToMany,
};

/**
 * Signup model.
 */
#[Entity]
#[InheritanceType(value: "SINGLE_TABLE")]
#[DiscriminatorColumn(
    name: "type",
    type: "string",
)]
#[DiscriminatorMap(value: ["user" => "UserSignup", "external" => "ExternalSignup"])]
abstract class Signup
{
    /**
     * ID for the signup.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "IDENTITY")]
    protected int $id;

    /**
     * The SignupList the signup is for.
     */
    #[ManyToOne(
        targetEntity: "Activity\Model\SignupList",
        inversedBy: "signUps",
    )]
    #[JoinColumn(
        name: "signuplist_id",
        referencedColumnName: "id",
        nullable: false,
    )]
    protected SignupList $signupList;

    /**
     * Additional field values for this Signup.
     */
    #[OneToMany(
        targetEntity: "Activity\Model\SignupFieldValue",
        mappedBy: "signup",
        cascade: ["persist", "remove"],
    )]
    protected ArrayCollection $fieldValues;

    /**
     * Get the signup id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get the SignupList which the user is signed up for.
     *
     * @return SignupList
     */
    public function getSignupList(): SignupList
    {
        return $this->signupList;
    }

    /**
     * Set the SignupList that the user signed up for.
     */
    public function setSignupList(SignupList $signupList): void
    {
        $this->signupList = $signupList;
    }

    /**
     * Get all the extra field values.
     *
     * @return ArrayCollection
     */
    public function getFieldValues(): ArrayCollection
    {
        return $this->fieldValues;
    }

    /**
     * Get the full name of the user whom signed up for the SignupList.
     *
     * @return string
     */
    abstract public function getFullName(): string;

    /**
     * Get the email address of the user whom signed up for the SignupList.
     *
     * @return string
     */
    abstract public function getEmail(): string;
}
