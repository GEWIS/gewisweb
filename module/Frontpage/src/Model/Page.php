<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
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
    use IdentifiableTrait;

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
    protected ?string $subCategory = null;

    /**
     * Name of the page.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $name = null;

    /**
     * Dutch title of the page.
     */
    #[Column(type: "string")]
    protected string $dutchTitle;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: "text")]
    protected string $dutchContent;

    /**
     * English title of the page.
     */
    #[Column(type: "string")]
    protected string $englishTitle;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: "text")]
    protected string $englishContent;

    /**
     * The minimal role required to view a page.
     */
    #[Column(type: "string")]
    protected string $requiredRole;

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
     * @param string|null $subCategory
     */
    public function setSubCategory(?string $subCategory): void
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
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
