<?php

declare(strict_types=1);

namespace App\Entity\Activity;

use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Application\LocalisedText as LocalisedTextModel;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Activity\SignupFieldRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OrderBy;

/**
 * SignupField model.
 *
 * @psalm-type SignupFieldArrayType = array{
 *     id: int,
 *     sensitive: bool,
 *     name: ?string,
 *     nameEn: ?string,
 *     type: string,
 *     minimumValue: ?int,
 *     maximumValue: ?int,
 *     options: array<array-key, ?string>,
 *     optionsEn: array<array-key, ?string>,
 * }
 * @psalm-import-type LocalisedTextGdprArrayType from LocalisedTextModel as ImportedLocalisedTextGdprArrayType
 * @psalm-import-type SignupOptionGdprArrayType from SignupOption as ImportedSignupOptionGdprArrayType
 * @psalm-type SignupFieldGdprArrayType = array{
 *     id: int,
 *     sensitive: bool,
 *     name: ImportedLocalisedTextGdprArrayType,
 *     type: string,
 *     minimumValue: ?int,
 *     maximumValue: ?int,
 *     options: ?ImportedSignupOptionGdprArrayType[],
 * }
 */
#[Entity(repositoryClass: SignupFieldRepository::class)]
class SignupField
{
    use IdentifiableTrait;

    /**
     * Activity that the SignupField belongs to.
     */
    #[ManyToOne(
        targetEntity: SignupList::class,
        cascade: ['persist'],
        inversedBy: 'fields',
    )]
    #[JoinColumn(
        name: 'signuplist_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private SignupList $signupList;

    /**
     * The name of the SignupField.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private ActivityLocalisedText $name;

    /**
     * Whether this SignupField is sensitive. If it is sensitive, it is only visible to the board and the organiser of
     * the activity.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $isSensitive = false;

    /**
     * The type of the SignupField.
     */
    #[Column(
        type: Types::STRING,
        enumType: SignupFieldTypes::class,
    )]
    private SignupFieldTypes $type = SignupFieldTypes::Text;

    /**
     * The minimal value constraint for the ``number'' type.
     */
    #[Column(
        type: Types::INTEGER,
        nullable: true,
    )]
    private ?int $minimumValue = null;

    /**
     * The maximal value constraint for the ``number'' type.
     */
    #[Column(
        type: Types::INTEGER,
        nullable: true,
    )]
    private ?int $maximumValue = null;

    /**
     * The position of this field among the sign-up list's fields; the organiser reorders them in the editor and this
     * fixes the display order (lower first) everywhere the fields are listed. The id is the tiebreaker (see the
     * ``SignupList::$fields'' ordering) so pre-existing fields keep their relative order.
     */
    #[Column(
        type: Types::INTEGER,
        options: ['default' => 0],
    )]
    private int $position = 0;

    /**
     * The allowed options for the SignupField of the ``option'' type.
     *
     * @var Collection<array-key, SignupOption>
     */
    #[OneToMany(
        targetEntity: SignupOption::class,
        mappedBy: 'field',
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[OrderBy([
        'position' => 'ASC',
        'id' => 'ASC',
    ])]
    private Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
        // Form-ready defaults; Doctrine bypasses the constructor when hydrating existing rows.
        $this->name = new ActivityLocalisedText();
    }

    public function addOption(SignupOption $option): void
    {
        if ($this->options->contains($option)) {
            return;
        }

        $this->options->add($option);
        $option->setField($this);
    }

    public function removeOption(SignupOption $option): void
    {
        $this->options->removeElement($option);
    }

    public function getSignupList(): SignupList
    {
        return $this->signupList;
    }

    public function setSignupList(SignupList $signupList): void
    {
        $this->signupList = $signupList;
    }

    public function isSensitive(): bool
    {
        return $this->isSensitive;
    }

    public function setIsSensitive(bool $isSensitive): void
    {
        $this->isSensitive = $isSensitive;
    }

    /**
     * @return Collection<array-key, SignupOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function getName(): ActivityLocalisedText
    {
        return $this->name;
    }

    public function setName(ActivityLocalisedText $name): void
    {
        $this->name = $name;
    }

    public function getType(): SignupFieldTypes
    {
        return $this->type;
    }

    public function setType(SignupFieldTypes $type): void
    {
        $this->type = $type;
    }

    public function getMinimumValue(): ?int
    {
        return $this->minimumValue;
    }

    public function setMinimumValue(?int $minimumValue): void
    {
        $this->minimumValue = $minimumValue;
    }

    public function getMaximumValue(): ?int
    {
        return $this->maximumValue;
    }

    public function setMaximumValue(?int $maximumValue): void
    {
        $this->maximumValue = $maximumValue;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Returns an associative array representation of this object.
     *
     * @return SignupFieldArrayType
     */
    public function toArray(): array
    {
        $optionsArrays = [];
        $optionsEn = [];

        foreach ($this->getOptions() as $option) {
            $optionData = $option->toArray();
            $optionsArrays[] = $optionData['value'];
            $optionsEn[] = $optionData['valueEn'];
        }

        return [
            'id' => $this->getId(),
            'sensitive' => $this->isSensitive(),
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'type' => $this->getType()->value,
            'minimumValue' => $this->getMinimumValue(),
            'maximumValue' => $this->getMaximumValue(),
            'options' => $optionsArrays,
            'optionsEn' => $optionsEn,
        ];
    }

    /**
     * @return SignupFieldGdprArrayType
     */
    public function toGdprArray(): array
    {
        /** @var ImportedSignupOptionGdprArrayType[] $options */
        $options = [];
        foreach ($this->getOptions() as $option) {
            $options[] = $option->toGdprArray();
        }

        return [
            'id' => $this->getId(),
            'sensitive' => $this->isSensitive(),
            'name' => $this->getName()->toGdprArray(),
            'type' => $this->getType()->value,
            'minimumValue' => $this->getMinimumValue(),
            'maximumValue' => $this->getMaximumValue(),
            'options' => $options,
        ];
    }
}
