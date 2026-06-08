<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Application\Traits\TimestampableTrait;
use App\Repository\Activity\SignupRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Symfony\Contracts\Translation\TranslatorInterface;

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
 *     agreedToPolicyAt: ?string,
 * }
 */
#[Entity(repositoryClass: SignupRepository::class)]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: Types::STRING,
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
    private SignupList $signupList;

    /**
     * Additional field values for this Signup.
     *
     * @var Collection<array-key, SignupFieldValue>
     */
    #[OneToMany(
        targetEntity: SignupFieldValue::class,
        mappedBy: 'signup',
        cascade: [
            'persist',
            'remove',
        ],
    )]
    private Collection $fieldValues;

    /**
     * Determines if the user was present or not
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $present = false;

    /**
     * Whether this sign-up has been admitted (drawn). Defaults to false -- the safe default: on a limited-capacity
     * list a sign-up starts on the waiting list until a draw admits it, so a creation path that forgets to set this
     * can never silently bypass capacity. An unlimited list has no draw, so its creation path admits explicitly
     * (drawn = !limitedCapacity); see ActivityFixture and the public subscribe flow.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $drawn = false;

    /**
     * When the person agreed to the activity and alcohol policies by submitting the sign-up form; null for sign-ups
     * created before this was recorded or added by an organiser on someone's behalf.
     */
    #[Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
    )]
    private ?DateTime $agreedToPolicyAt = null;

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
     * The formatted, localised answer this sign-up gave for a particular field, or an empty string if it has none.
     */
    public function displayValueForField(
        SignupField $field,
        TranslatorInterface $translator,
        Languages $language,
    ): string {
        foreach ($this->getFieldValues() as $fieldValue) {
            if ($fieldValue->getField()->getId() !== $field->getId()) {
                continue;
            }

            return $fieldValue->displayValue(
                $translator,
                $language,
            );
        }

        return '';
    }

    /**
     * Get presence of the user
     */
    public function isPresent(): bool
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
     * Get draw status of the user
     */
    public function isDrawn(): bool
    {
        return $this->drawn;
    }

    /**
     * Set the draw status of the user
     */
    public function setDrawn(bool $drawn): void
    {
        $this->drawn = $drawn;
    }

    /**
     * When the person agreed to the activity and alcohol policies, or null if not recorded.
     */
    public function getAgreedToPolicyAt(): ?DateTime
    {
        return $this->agreedToPolicyAt;
    }

    public function setAgreedToPolicyAt(?DateTime $agreedToPolicyAt): void
    {
        $this->agreedToPolicyAt = $agreedToPolicyAt;
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
     * @return SignupGdprArrayType
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
            'present' => $this->isPresent(),
            'agreedToPolicyAt' => $this->getAgreedToPolicyAt()?->format(DateTimeInterface::ATOM),
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

            if (SignupFieldTypes::Choice === $fieldValue->getField()->getType()) {
                $value = $fieldValue->getOption()?->getId();
            } elseif (SignupFieldTypes::YesNo === $fieldValue->getField()->getType()) {
                $value = 'Yes' === $fieldValue->getValue()
                    ? '1'
                    : '0';
            }

            $fieldValues[$fieldValue->getField()->getId()] = $value ?? $fieldValue->getValue();
        }

        return $fieldValues;
    }
}
