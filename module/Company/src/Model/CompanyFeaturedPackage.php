<?php

declare(strict_types=1);

namespace Company\Model;

use Company\Model\Enums\CompanyPackageTypes;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Exception;
use Override;

/**
 * CompanyFeaturedPackage model.
 *
 * @psalm-type CompanyFeaturedPackageArrayType = array{
 *     contractNumber: ?string,
 *     startDate: string,
 *     expirationDate: string,
 *     published: bool,
 *     article: ?string,
 *     articleEn: ?string,
 * }
 */
#[Entity]
class CompanyFeaturedPackage extends CompanyPackage
{
    /**
     * The featured package content article. This column should be nullable (the default), as this entity is part of the
     * {@link \Company\Model\CompanyPackage} discriminator map.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
    )]
    #[JoinColumn(
        name: 'article_id',
        referencedColumnName: 'id',
    )]
    protected CompanyLocalisedText $article;

    public function __construct()
    {
        parent::__construct();

        $this->article = new CompanyLocalisedText(null, null);
    }

    /**
     * Get the featured package's article text.
     */
    public function getArticle(): CompanyLocalisedText
    {
        return $this->article;
    }

    /**
     * Set the featured package's article text.
     */
    public function setArticle(CompanyLocalisedText $article): void
    {
        $this->article = $article;
    }

    #[Override]
    public function getType(): CompanyPackageTypes
    {
        return CompanyPackageTypes::Featured;
    }

    /**
     * @return CompanyFeaturedPackageArrayType
     */
    #[Override]
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['article'] = $this->getArticle()->getValueNL();
        $array['articleEn'] = $this->getArticle()->getValueEN();

        return $array;
    }

    /**
     * @psalm-param array{
     *     contractNumber: ?string,
     *     startDate: string,
     *     expirationDate: string,
     *     published: bool,
     *     article: ?string,
     *     articleEn: ?string,
     * } $data
     *
     * @throws Exception
     */
    #[Override]
    public function exchangeArray(array $data): void
    {
        parent::exchangeArray($data);

        $this->getArticle()->updateValues($data['articleEn'], $data['article']);
    }
}
