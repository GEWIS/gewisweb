<?php

namespace Company\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Job Category model.
 *
 * @ORM\Entity
 */
class JobCategory
{
    /**
     * Constructor.
     */
    public function __construct()
    {
    }
    /**
     * The category id.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     *
     * The name of the category
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     *
     * The name of the category
     *
     * @ORM\Column(type="string")
     */
    protected $pluralName;

    /**
     *
     * The slug of the category
     *
     * @ORM\Column(type="string")
     */
    protected $slug;

    /**
     *
     * The language of the category
     *
     * @ORM\Column(type="string")
     */
    protected $language;

    /**
     *
     * If the category is hidden
     *
     * @ORM\Column(type="boolean")
     */
    protected $hidden;

    /**
     * Get's the id
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set's the id
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * The category id.
     *
     * @ORM\Column(type="integer")
     */
    protected $languageNeutralId;

    /**
     * Get's the id
     */
    public function getLanguageNeutralId()
    {
        return $this->languageNeutralId;
    }

    /**
     * Set's the id
     */
    public function setLanguageNeutralId($languageNeutralId)
    {
        $this->languageNeutralId = $languageNeutralId;
    }

    /**
     * Get's the id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set's the id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get's the name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get's the plural name
     */
    public function getPluralName()
    {
        return $this->pluralName;
    }

    /**
     * Set's the name
     */
    public function setPluralName($name)
    {
        $this->pluralName = $name;
    }

    /**
     * Set's the name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get's the slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set's the slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * Get's the language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set's the language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
}
