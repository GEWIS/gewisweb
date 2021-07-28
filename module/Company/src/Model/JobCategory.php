<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
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
    protected int $id;

    /**
     * The name of the category.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * The name of the category.
     */
    #[Column(type: "string")]
    protected string $pluralName;

    /**
     * The slug of the category.
     */
    #[Column(type: "string")]
    protected string $slug;

    /**
     * The language of the category.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * If the category is hidden.
     */
    #[Column(type: "boolean")]
    protected bool $hidden;

    /**
     * The category id.
     */
    #[Column(type: "integer")]
    protected int $languageNeutralId;

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
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Get's the id.
     *
     * @return int
     */
    public function getLanguageNeutralId(): int
    {
        return $this->languageNeutralId;
    }

    /**
     * Set's the id.
     *
     * @param int $languageNeutralId
     */
    public function setLanguageNeutralId(int $languageNeutralId): void
    {
        $this->languageNeutralId = $languageNeutralId;
    }

    /**
     * Get's the id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set's the id.
     *
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get's the name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get's the plural name.
     *
     * @return string
     */
    public function getPluralName(): string
    {
        return $this->pluralName;
    }

    /**
     * Set's the name.
     *
     * @param string $name
     */
    public function setPluralName(string $name): void
    {
        $this->pluralName = $name;
    }

    /**
     * Set's the name.
     *
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get's the slug.
     *
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Set's the slug.
     */
    public function setSlug($slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Get's the language.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set's the language.
     *
     * @param string $language
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }
}
