<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

//use Doctrine\Common\Collections\ArrayCollection;
//use Zend\Permissions\Acl\Role\RoleInterface;
//use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * CompanyFeaturedPackage model.
 *
 * @ORM\Entity
 */
class CompanyFeaturedPackage extends CompanyPackage //implements RoleInterface, ResourceInterface
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // todo
    }

    /**
     * The featured package content article.
     *
     * @ORM\Column(type="text")
     */
    protected $article;

    /**
     * Get the featured package's article text
     *
     * @return string
     */
    public function getArticle()
    {
        return $this->article;
    }

    /**
     * Set the featured package's article text
     *
     * @param string $image
     */
    public function setArticle($article)
    {
        $this->article = $article;
    }

    /**
     * The package's language.
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     * Get the package's language.
     *
     * @return string language of the package
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the package's language.
     *
     * @param string $language language of the package
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
    // For zend2 forms
    public function getArrayCopy()
    {
        $array = parent::getArrayCopy();
        $array['language'] = $this->getLanguage();
        $array['article'] = $this->getArticle();
        return $array;
    }

    public function exchangeArray($data)
    {
        parent::exchangeArray($data);
        $this->setLanguage((isset($data['language'])) ? $data['language'] : $this->getLanguage());
        $this->setArticle((isset($data['article'])) ? $data['article'] : $this->getArticle());
    }
}
