<?php

namespace Frontpage\Model;

use Doctrine\ORM\Mapping as ORM;
use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * News item
 *
 * @ORM\Entity
 * @ORM\Table(name="NewsItem")
 */
class NewsItem implements ResourceInterface
{

    /**
     * News item ID.
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * The date the news item was written.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * Dutch title of the news.
     *
     * @ORM\Column(type="string")
     */
    protected $dutchTitle;

    /**
     * English title of the news.
     *
     * @ORM\Column(type="string")
     */
    protected $englishTitle;

    /**
     * The english HTML content of the news.
     *
     * @ORM\Column(type="text")
     */
    protected $englishContent;

    /**
     * The english HTML content of the news.
     *
     * @ORM\Column(type="text")
     */
    protected $dutchContent;

    /**
     * @return mixed
     */
    public function getPinned()
    {
        return $this->pinned;
    }

    /**
     * @param mixed $pinned
     */
    public function setPinned($pinned)
    {
        $this->pinned = $pinned;
    }

    /**
     * Whether this news item is pinned to the top of the news section or not
     *
     * @ORM\Column(type="boolean")
     */
    protected $pinned;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
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
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
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

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId()
    {
        return 'news_item';
    }
}
