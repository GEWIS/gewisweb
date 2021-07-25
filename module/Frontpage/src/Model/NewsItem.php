<?php

namespace Frontpage\Model;

use DateTime;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
};
use Laminas\Permissions\Acl\Resource\ResourceInterface;

/**
 * News item.
 */
#[Entity]
class NewsItem implements ResourceInterface
{
    /**
     * News item ID.
     */
    #[Id]
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected int $id;

    /**
     * The date the news item was written.
     */
    #[Column(type: "date")]
    protected DateTime $date;

    /**
     * Dutch title of the news.
     */
    #[Column(type: "string")]
    protected string $dutchTitle;

    /**
     * English title of the news.
     */
    #[Column(type: "string")]
    protected string $englishTitle;

    /**
     * The english HTML content of the news.
     */
    #[Column(type: "text")]
    protected string $englishContent;

    /**
     * The english HTML content of the news.
     */
    #[Column(type: "text")]
    protected string $dutchContent;

    /**
     * Whether this news item is pinned to the top of the news section or not.
     */
    #[Column(type: "boolean")]
    protected bool $pinned;

    /**
     * @return bool
     */
    public function getPinned(): bool
    {
        return $this->pinned;
    }

    /**
     * @param bool $pinned
     */
    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getDutchTitle(): string
    {
        return $this->dutchTitle;
    }

    /**
     * @return string
     */
    public function getEnglishTitle(): string
    {
        return $this->englishTitle;
    }

    /**
     * @return string
     */
    public function getEnglishContent(): string
    {
        return $this->englishContent;
    }

    /**
     * @return string
     */
    public function getDutchContent(): string
    {
        return $this->dutchContent;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @param string $dutchTitle
     */
    public function setDutchTitle(string $dutchTitle): void
    {
        $this->dutchTitle = $dutchTitle;
    }

    /**
     * @param string $englishTitle
     */
    public function setEnglishTitle(string $englishTitle): void
    {
        $this->englishTitle = $englishTitle;
    }

    /**
     * @param string $englishContent
     */
    public function setEnglishContent(string $englishContent): void
    {
        $this->englishContent = $englishContent;
    }

    /**
     * @param string $dutchContent
     */
    public function setDutchContent(string $dutchContent): void
    {
        $this->dutchContent = $dutchContent;
    }

    /**
     * Get the resource ID.
     *
     * @return string
     */
    public function getResourceId(): string
    {
        return 'news_item';
    }
}
