<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    UniqueConstraint,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Page.
 */
#[Entity]
#[UniqueConstraint(
    name: "page_idx",
    columns: ["category", "subCategory", "name"],
)]
class Page implements ResourceInterface
{
    /**
     * Tag ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    /**
     * Dutch title of the page.
     */
    #[Column(type: "string")]
    protected string $dutchTitle;

    /**
     * English title of the page.
     */
    #[Column(type: "string")]
    protected string $englishTitle;

    /**
     * Category of the page.
     */
    #[Column(type: "string")]
    protected string $category;

    /**
     * Sub-category of the page.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $subCategory;

    /**
     * Name of the page.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $name;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: "string")]
    protected string $englishContent;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: "string")]
    protected string $dutchContent;

    /**
     * The minimal role required to view a page.
     */
    #[Column(type: "string")]
    protected string $requiredRole;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDutchTitle(): string
    {
        return $this->dutchTitle;
    }

    /**
     * @return string
     */
    public function getEnglishTitle(): string
    {
        return $this->englishTitle;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEnglishContent(): string
    {
        return $this->englishContent;
    }

    /**
     * @return string
     */
    public function getDutchContent(): string
    {
        return $this->dutchContent;
    }

    /**
     * @return string
     */
    public function getRequiredRole(): string
    {
        return $this->requiredRole;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @param string $dutchTitle
     */
    public function setDutchTitle(string $dutchTitle): void
    {
        $this->dutchTitle = $dutchTitle;
    }

    /**
     * @param string $englishTitle
     */
    public function setEnglishTitle(string $englishTitle): void
    {
        $this->englishTitle = $englishTitle;
    }

    /**
     * @param string $category
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * @param string $subCategory
     */
    public function setSubCategory(string $subCategory): void
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $englishContent
     */
    public function setEnglishContent(string $englishContent): void
    {
        $this->englishContent = $englishContent;
    }

    /**
     * @param string $dutchContent
     */
    public function setDutchContent(string $dutchContent): void
    {
        $this->dutchContent = $dutchContent;
    }

    /**
     * @param string $requiredRole
     */
    public function setRequiredRole(string $requiredRole): void
    {
        $this->requiredRole = $requiredRole;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'page' . $this->getId();
    }
}
