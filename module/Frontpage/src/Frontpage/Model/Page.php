<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * Tag.
 *
 * @ORM\Entity
 * @ORM\Table(name="Page",uniqueConstraints={@ORM\UniqueConstraint(name="page_idx", columns={"category", "subCategory", "name"})})
 */
class Page implements ResourceInterface
{
    /**
     * Tag ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Dutch title of the page
     *
     * @ORM\Column(type="string")
     */
    protected $dutchTitle;

    /**
     * English title of the page
     *
     * @ORM\Column(type="string")
     */
    protected $englishTitle;

    /**
     * Category of the page
     *
     * @ORM\Column(type="string")
     */
    protected $category;

    /**
     * Sub-category of the page
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $subCategory;

    /**
     * Name of the page
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * The english HTML content of the page
     *
     * @ORM\Column(type="text")
     */
    protected $englishContent;

    /**
     * The english HTML content of the page
     *
     * @ORM\Column(type="text")
     */
    protected $dutchContent;

    /**
     * The minimal role required to view a page.
     *
     * @ORM\Column(type="string")
     */
    protected $requiredRole;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDutchTitle()
    {
        return $this->dutchTitle;
    }

    /**
     * @return string
     */
    public function getEnglishTitle()
    {
        return $this->englishTitle;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return string|null
     */
    public function getSubCategory()
    {
        return $this->subCategory;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEnglishContent()
    {
        return $this->englishContent;
    }

    /**
     * @return string
     */
    public function getDutchContent()
    {
        return $this->dutchContent;
    }

    /**
     * @return string
     */
    public function getRequiredRole()
    {
        return $this->requiredRole;
    }

    /**
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $dutchTitle
     */
    public function setDutchTitle($dutchTitle)
    {
        $this->dutchTitle = $dutchTitle;
    }

    /**
     * @param string $englishTitle
     */
    public function setEnglishTitle($englishTitle)
    {
        $this->englishTitle = $englishTitle;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @param string $subCategory
     */
    public function setSubCategory($subCategory)
    {
        $this->subCategory = $subCategory;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $englishContent
     */
    public function setEnglishContent($englishContent)
    {
        $this->englishContent = $englishContent;
    }

    /**
     * @param string $dutchContent
     */
    public function setDutchContent($dutchContent)
    {
        $this->dutchContent = $dutchContent;
    }

    public function setRequiredRole($requiredRole)
    {
        $this->requiredRole = $requiredRole;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'page';
    }
}
