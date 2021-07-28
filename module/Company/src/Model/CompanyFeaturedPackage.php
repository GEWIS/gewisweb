<?php

namespace Company\Model;

use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};
use Exception;

/**
 * CompanyFeaturedPackage model.
 */
#[Entity]
class CompanyFeaturedPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * The featured package content article.
     */
    #[Column(type: "text")]
    protected string $article;

    /**
     * The package's language.
     */
    #[Column(type: "string")]
    protected string $language;

    /**
     * Get the featured package's article text.
     *
     * @return string
     */
    public function getArticle(): string
    {
        return $this->article;
    }

    /**
     * Set the featured package's article text.
     *
     * @param string $article
     */
    public function setArticle(string $article): void
    {
        $this->article = $article;
    }

    /**
     * Get the package's language.
     *
     * @return string language of the package
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the package's language.
     *
     * @param string $language language of the package
     */
    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    // For zend2 forms
    /**
     * @return array
     */
    public function getArrayCopy(): array
    {
        $array = parent::getArrayCopy();
        $array['language'] = $this->getLanguage();
        $array['article'] = $this->getArticle();

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
        $this->setLanguage((isset($data['language'])) ? $data['language'] : $this->getLanguage());
        $this->setArticle((isset($data['article'])) ? $data['article'] : $this->getArticle());
    }
}
