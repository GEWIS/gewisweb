<?php

declare(strict_types=1);

namespace Company\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Job Category model.
 */
#[Entity]
class JobCategory
{
    use IdentifiableTrait;

    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $name;

    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'pluralName_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $pluralName;

    /**
     * The slug of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'slug_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected CompanyLocalisedText $slug;

    /**
     * If the category is hidden.
     */
    #[Column(type: 'boolean')]
    protected bool $hidden;

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set's the id.
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Gets the name.
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the plural name.
     */
    public function getPluralName(): CompanyLocalisedText
    {
        return $this->pluralName;
    }

    /**
     * Sets the name.
     */
    public function setPluralName(CompanyLocalisedText $name): void
    {
        $this->pluralName = $name;
    }

    /**
     * Gets the slug.
     */
    public function getSlug(): CompanyLocalisedText
    {
        return $this->slug;
    }

    /**
     * Sets the slug.
     */
    public function setSlug(CompanyLocalisedText $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return array{
     *     id: int,
     *     name: ?string,
     *     nameEn: ?string,
     *     pluralName: ?string,
     *     pluralNameEn: ?string,
     *     slug: ?string,
     *     slugEn: ?string,
     *     hidden: bool,
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'pluralName' => $this->getPluralName()->getValueNL(),
            'pluralNameEn' => $this->getPluralName()->getValueEN(),
            'slug' => $this->getSlug()->getValueNL(),
            'slugEn' => $this->getSlug()->getValueEN(),
            'hidden' => $this->getHidden(),
        ];
    }
}
