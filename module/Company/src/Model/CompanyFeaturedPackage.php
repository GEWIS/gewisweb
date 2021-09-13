<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Entity,
    OneToOne,
};
use Exception;

/**
 * CompanyFeaturedPackage model.
 */
#[Entity]
class CompanyFeaturedPackage extends CompanyPackage
{
    /**
     * The featured package content article.
     */
    #[OneToOne(
        targetEntity: CompanyLocalisedText::class,
        cascade: ["persist", "remove"],
        orphanRemoval: true,
    )]
    protected CompanyLocalisedText $article;

    /**
     * CompanyFeaturedPackage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->article = new CompanyLocalisedText(null, null);
    }

    /**
     * Get the featured package's article text.
     *
     * @return CompanyLocalisedText
     */
    public function getArticle(): CompanyLocalisedText
    {
        return $this->article;
    }

    /**
     * Set the featured package's article text.
     *
     * @param CompanyLocalisedText $article
     */
    public function setArticle(CompanyLocalisedText $article): void
    {
        $this->article = $article;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['article'] = $this->getArticle()->getValueNL();
        $array['articleEn'] = $this->getArticle()->getValueEN();

        return $array;
    }

    /**
     * @param array $data
     *
     * @throws Exception
     */
    public function exchangeArray(array $data): void
    {
        parent::exchangeArray($data);
        $this->getArticle()->updateValues($data['articleEn'], $data['article']);
    }
}
