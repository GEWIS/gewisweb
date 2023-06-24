<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * Signup model.
 */
#[Entity]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'user' => UserSignup::class,
        'external' => ExternalSignup::class,
    ],
)]
#[HasLifecycleCallbacks]
abstract class Signup
{
    use IdentifiableTrait;
    use TimestampableTrait;

    /**
     * The SignupList the signup is for.
     */
    #[ManyToOne(
        targetEntity: SignupList::class,
        inversedBy: 'signUps',
    )]
    #[JoinColumn(
        name: 'signuplist_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected SignupList $signupList;

    /**
     * Additional field values for this Signup.
     *
     * @var Collection<array-key, SignupFieldValue>
     */
    #[OneToMany(
        targetEntity: SignupFieldValue::class,
        mappedBy: 'signup',
        cascade: ['persist', 'remove'],
    )]
    protected Collection $fieldValues;

    public function __construct()
    {
        $this->fieldValues = new ArrayCollection();
    }

    /**
     * Get the SignupList which the user is signed up for.
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
     * @return Collection<array-key, SignupFieldValue>
     */
    public function getFieldValues(): Collection
    {
        return $this->fieldValues;
    }

    /**
     * Get the full name of the user whom signed up for the SignupList.
     */
    abstract public function getFullName(): string;

    /**
     * Get the email address of the user whom signed up for the SignupList.
     */
    abstract public function getEmail(): ?string;
}
