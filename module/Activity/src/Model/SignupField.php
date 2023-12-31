<?php

declare(strict_types=1);

namespace Activity\Model;

use Application\Model\LocalisedText as LocalisedTextModel;
use Application\Model\Traits\IdentifiableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * SignupField model.
 *
 * @psalm-type SignupFieldArrayType = array{
 *     id: int,
 *     sensitive: bool,
 *     name: ?string,
 *     nameEn: ?string,
 *     type: int,
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
 *     type: int,
 *     minimumValue: ?int,
 *     maximumValue: ?int,
 *     options: ?ImportedSignupOptionGdprArrayType[],
 * }
 */
#[Entity]
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
    protected SignupList $signupList;

    /**
     * The name of the SignupField.
     */
    #[OneToOne(
        targetEntity: ActivityLocalisedText::class,
        cascade: ['persist'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected ActivityLocalisedText $name;

    /**
     * Whether this SignupField is sensitive. If it is sensitive, it is only visible to the board and the organiser of
     * the activity.
     */
    #[Column(type: 'boolean')]
    protected bool $isSensitive = false;

    /**
     * The type of the SignupField.
     */
    #[Column(type: 'integer')]
    protected int $type;

    /**
     * The minimal value constraint for the ``number'' type.
     */
    #[Column(
        type: 'integer',
        nullable: true,
    )]
    protected ?int $minimumValue = null;

    /**
     * The maximal value constraint for the ``number'' type.
     */
    #[Column(
        type: 'integer',
        nullable: true,
    )]
    protected ?int $maximumValue = null;

    /**
     * The allowed options for the SignupField of the ``option'' type.
     *
     * @var Collection<array-key, SignupOption>
     */
    #[OneToMany(
        targetEntity: SignupOption::class,
        mappedBy: 'field',
        orphanRemoval: true,
    )]
    protected Collection $options;

    public function __construct()
    {
        $this->options = new ArrayCollection();
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

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
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
            'type' => $this->getType(),
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
            'type' => $this->getType(),
            'minimumValue' => $this->getMinimumValue(),
            'maximumValue' => $this->getMaximumValue(),
            'options' => $options,
        ];
    }
}
