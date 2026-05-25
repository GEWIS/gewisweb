<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Traits\IdentifiableTrait;
use App\Repository\Frontpage\NewsItemRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * News item.
 */
#[Entity(repositoryClass: NewsItemRepository::class)]
class NewsItem
{
    use IdentifiableTrait;

    /**
     * The date the news item was written.
     */
    #[Column(type: Types::DATE_MUTABLE)]
    private DateTime $date;

    /**
     * Dutch title of the news.
     */
    #[Column(type: Types::STRING)]
    private string $dutchTitle;

    /**
     * English title of the news.
     */
    #[Column(type: Types::STRING)]
    private string $englishTitle;

    /**
     * The english HTML content of the news.
     */
    #[Column(type: Types::TEXT)]
    private string $englishContent;

    /**
     * The english HTML content of the news.
     */
    #[Column(type: Types::TEXT)]
    private string $dutchContent;

    /**
     * Whether this news item is pinned to the top of the news section or not.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $pinned;

    public function getPinned(): bool
    {
        return $this->pinned;
    }

    public function setPinned(bool $pinned): void
    {
        $this->pinned = $pinned;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getDutchTitle(): string
    {
        return $this->dutchTitle;
    }

    public function getEnglishTitle(): string
    {
        return $this->englishTitle;
    }

    public function getEnglishContent(): string
    {
        return $this->englishContent;
    }

    public function getDutchContent(): string
    {
        return $this->dutchContent;
    }

    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    public function setDutchTitle(string $dutchTitle): void
    {
        $this->dutchTitle = $dutchTitle;
    }

    public function setEnglishTitle(string $englishTitle): void
    {
        $this->englishTitle = $englishTitle;
    }

    public function setEnglishContent(string $englishContent): void
    {
        $this->englishContent = $englishContent;
    }

    public function setDutchContent(string $dutchContent): void
    {
        $this->dutchContent = $dutchContent;
    }

    /**
     * Get the resource ID.
     */
    public function getResourceId(): string
    {
        return 'news_item';
    }
}
