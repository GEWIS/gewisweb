<?php

namespace Company\Model;

use Doctrine\Common\Collections\{
    ArrayCollection,
    Collection,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    OneToMany,
};

/**
 * Job Label model.
 */
#[Entity]
class JobLabel
{
    /**
     * The label id.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * The name of the label.
     */
    #[Column(type: "string")]
    protected string $name;

    /**
     * The slug of the label.
     */
    #[Column(type: "string")]
    protected string $slug;

    /**
     * The language of the label.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * The label id.
     */
    #[Column(type: "integer")]
    protected int $languageNeutralId;

    /**
     * The Assignments this Label belongs to.
     */
    #[OneToMany(
        targetEntity: "Company\Model\JobLabelAssignment",
        mappedBy: "label",
        cascade: ["persist"],
    )]
    protected Collection $assignments;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->assignments = new ArrayCollection();
    }

    /**
     * Get's the id.
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
     *
     * @return int
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
     *
     * @param string $slug
     */
    public function setSlug(string $slug): void
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
