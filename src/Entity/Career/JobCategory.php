<?php

declare(strict_types=1);

namespace App\Entity\Career;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Career\JobCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Job Category model.
 */
#[Entity(repositoryClass: JobCategoryRepository::class)]
class JobCategory
{
    use IdentifiableTrait;

    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
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
    private CareerLocalisedText $name;

    /**
     * The name of the category.
     */
    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'pluralName_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $pluralName;

    /**
     * The slug of the category.
     */
    #[OneToOne(
        targetEntity: CareerLocalisedText::class,
        cascade: [
            'persist',
            'remove',
        ],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'slug_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private CareerLocalisedText $slug;

    /**
     * If the category is hidden.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $hidden;

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
    public function getName(): CareerLocalisedText
    {
        return $this->name;
    }

    /**
     * Sets the name.
     */
    public function setName(CareerLocalisedText $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the plural name.
     */
    public function getPluralName(): CareerLocalisedText
    {
        return $this->pluralName;
    }

    /**
     * Sets the name.
     */
    public function setPluralName(CareerLocalisedText $name): void
    {
        $this->pluralName = $name;
    }

    /**
     * Gets the slug.
     */
    public function getSlug(): CareerLocalisedText
    {
        return $this->slug;
    }

    /**
     * Sets the slug.
     */
    public function setSlug(CareerLocalisedText $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return array{
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
