<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\Traits\IdentifiableTrait;
use Application\Model\Traits\TimestampableTrait;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
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
 *
 * @psalm-import-type SignupFieldValueGdprArrayType from SignupFieldValue as ImportedSignupFieldValueGdprArrayType
 * @psalm-type SignupGdprArrayType = array{
 *     id: int,
 *     createdAt: string,
 *     updatedAt: string,
 *     activity_id: int,
 *     signupList_id: int,
 *     fieldValues: ImportedSignupFieldValueGdprArrayType[],
 *     present: bool,
 * }
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

    /**
     * Determines if the user was present or not
     */
    #[Column(type: 'boolean')]
    protected bool $present = false;

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
     * Get presence of the user
     */
    public function getPresent(): bool
    {
        return $this->present;
    }

    /**
     * Set presence of the user
     */
    public function setPresent(bool $present): void
    {
        $this->present = $present;
    }

    /**
     * Get the full name of the user whom signed up for the SignupList.
     */
    abstract public function getFullName(): string;

    /**
     * Get the email address of the user whom signed up for the SignupList.
     */
    abstract public function getEmail(): ?string;

    /**
     * @return ((((string|null)[]|int)[]|int|string|null)[][]|int|string|null)[]
     * @psalm-return array{id: int|null, createdAt: string, updatedAt: string, activity_id: int|null, signupList_id: int|null, fieldValues: array<array{id: int, value: string|null, option: array{id: int, value: array{valueEN: string|null, valueNL: string|null}}|null}>}
     */
    public function toGdprArray(): array
    {
        /** @var ImportedSignupFieldValueGdprArrayType[] $fieldValues */
        $fieldValues = [];
        foreach ($this->getFieldValues() as $fieldValue) {
            $fieldValues[] = $fieldValue->toGdprArray();
        }

        return [
            'id' => $this->getId(),
            'createdAt' => $this->getCreatedAt()->format(DateTimeInterface::ATOM),
            'updatedAt' => $this->getUpdatedAt()->format(DateTimeInterface::ATOM),
            'activity_id' => $this->getSignupList()->getActivity()->getId(),
            'signupList_id' => $this->getSignupList()->getId(),
            'fieldValues' => $fieldValues,
        ];
    }

    /**
     * @return array<array-key, int|string|null>
     */
    public function toFormArray(): array
    {
        $fieldValues = [];
        foreach ($this->getFieldValues() as $fieldValue) {
            $value = null;

            if (3 === $fieldValue->getField()->getType()) {
                $value = $fieldValue->getOption()?->getId();
            } elseif (1 === $fieldValue->getField()->getType()) {
                $value = 'Yes' === $fieldValue->getValue() ? '1' : '0';
            }

            $fieldValues[$fieldValue->getField()->getId()] = $value ?? $fieldValue->getValue();
        }

        return $fieldValues;
    }
}
