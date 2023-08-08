<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Model\Enums\UserRoles;

/**
 * Page.
 */
#[Entity]
#[UniqueConstraint(
    name: 'page_idx',
    columns: ['category', 'subCategory', 'name'],
)]
class Page implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Category of the page.
     */
    #[Column(type: 'string')]
    protected string $category;

    /**
     * Sub-category of the page.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $subCategory = null;

    /**
     * Name of the page.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $name = null;

    /**
     * Dutch title of the page.
     */
    #[Column(type: 'string')]
    protected string $dutchTitle;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: 'text')]
    protected string $dutchContent;

    /**
     * English title of the page.
     */
    #[Column(type: 'string')]
    protected string $englishTitle;

    /**
     * The english HTML content of the page.
     */
    #[Column(type: 'text')]
    protected string $englishContent;

    /**
     * The minimal role required to view a page.
     */
    #[Column(
        type: 'string',
        enumType: UserRoles::class,
    )]
    protected UserRoles $requiredRole;

    public function getDutchTitle(): string
    {
        return $this->dutchTitle;
    }

    public function getEnglishTitle(): string
    {
        return $this->englishTitle;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getSubCategory(): ?string
    {
        return $this->subCategory;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEnglishContent(): string
    {
        return $this->englishContent;
    }

    public function getDutchContent(): string
    {
        return $this->dutchContent;
    }

    public function getRequiredRole(): UserRoles
    {
        return $this->requiredRole;
    }

    public function setDutchTitle(string $dutchTitle): void
    {
        $this->dutchTitle = $dutchTitle;
    }

    public function setEnglishTitle(string $englishTitle): void
    {
        $this->englishTitle = $englishTitle;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function setSubCategory(?string $subCategory): void
    {
        $this->subCategory = $subCategory;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setEnglishContent(string $englishContent): void
    {
        $this->englishContent = $englishContent;
    }

    public function setDutchContent(string $dutchContent): void
    {
        $this->dutchContent = $dutchContent;
    }

    public function setRequiredRole(UserRoles $requiredRole): void
    {
        $this->requiredRole = $requiredRole;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'page' . $this->getId();
    }
}
