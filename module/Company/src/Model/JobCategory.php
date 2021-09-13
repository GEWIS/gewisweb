<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    OneToOne,
};

/**
 * Job Category model.
 */
#[Entity]
class JobCategory
{
    /**
     * The category id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $name;


    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $pluralName;


    /**
     * The slug of the category.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $slug;

    /**
     * If the category is hidden.
     */
    #[Column(type: "boolean")]
    protected bool $hidden;

    /**
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set's the id.
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Gets the id.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets the id.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Gets the name.
     *
     * @return CompanyLocalisedText
     */
    public function getName(): CompanyLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param CompanyLocalisedText $name
     */
    public function setName(CompanyLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the plural name.
     *
     * @return CompanyLocalisedText
     */
    public function getPluralName(): CompanyLocalisedText
    {
        return $this->pluralName;
    }

    /**
     * Sets the name.
     *
     * @param CompanyLocalisedText $name
     */
    public function setPluralName(CompanyLocalisedText $name): void
    {
        $this->pluralName = $name;
    }

    /**
     * Gets the slug.
     *
     * @return CompanyLocalisedText
     */
    public function getSlug(): CompanyLocalisedText
    {
        return $this->slug;
    }

    /**
     * Sets the slug.
     *
     * @param CompanyLocalisedText $slug
     */
    public function setSlug(CompanyLocalisedText $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return array
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
