<?php

declare(strict_types=1);

namespace Frontpage\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use User\Model\Enums\UserRoles;

/**
 * Page.
 */
#[Entity]
class Page implements ResourceInterface
{
    use IdentifiableTrait;

    /**
     * Category of the page.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'category_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $category;

    /**
     * Sub-category of the page.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'subCategory_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $subCategory;

    /**
     * Name of the page.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'name_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $name;

    /**
     * Title of the page.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'title_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $title;

    /**
     * The HTML content of the page.
     */
    #[OneToOne(
        targetEntity: FrontpageLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'content_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    protected FrontpageLocalisedText $content;

    /**
     * The minimal role required to view a page.
     */
    #[Column(
        type: 'string',
        enumType: UserRoles::class,
    )]
    protected UserRoles $requiredRole;

    public function getCategory(): FrontpageLocalisedText
    {
        return $this->category;
    }

    public function setCategory(FrontpageLocalisedText $category): void
    {
        $this->category = $category;
    }

    public function getSubCategory(): FrontpageLocalisedText
    {
        return $this->subCategory;
    }

    public function setSubCategory(FrontpageLocalisedText $subCategory): void
    {
        $this->subCategory = $subCategory;
    }

    public function getName(): FrontpageLocalisedText
    {
        return $this->name;
    }

    public function setName(FrontpageLocalisedText $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): FrontpageLocalisedText
    {
        return $this->title;
    }

    public function setTitle(FrontpageLocalisedText $title): void
    {
        $this->title = $title;
    }

    public function getContent(): FrontpageLocalisedText
    {
        return $this->content;
    }

    public function setContent(FrontpageLocalisedText $content): void
    {
        $this->content = $content;
    }

    public function getRequiredRole(): UserRoles
    {
        return $this->requiredRole;
    }

    public function setRequiredRole(UserRoles $requiredRole): void
    {
        $this->requiredRole = $requiredRole;
    }

    /**
     * @return array{
     *     categoryEn: ?string,
     *     category: ?string,
     *     subCategoryEn: ?string,
     *     subCategory: ?string,
     *     nameEn: ?string,
     *     name: ?string,
     *     titleEn: ?string,
     *     title: ?string,
     *     contentEn: ?string,
     *     content: ?string,
     *     requiredRole: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'categoryEn' => $this->getCategory()->getValueEN(),
            'category' => $this->getCategory()->getValueNL(),
            'subCategoryEn' => $this->getSubCategory()->getValueEN(),
            'subCategory' => $this->getSubCategory()->getValueNL(),
            'nameEn' => $this->getName()->getValueEN(),
            'name' => $this->getName()->getValueNL(),
            'titleEn' => $this->getTitle()->getValueEN(),
            'title' => $this->getTitle()->getValueNL(),
            'contentEn' => $this->getContent()->getValueEN(),
            'content' => $this->getContent()->getValueNL(),
            'requiredRole' => $this->getRequiredRole()->value,
        ];
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'page' . $this->getId();
    }
}
