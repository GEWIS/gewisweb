<?php

declare(strict_types=1);

namespace App\Entity\Frontpage;

use App\Entity\Application\Enums\Languages;
use App\Entity\Application\Traits\IdentifiableTrait;
use App\Entity\Frontpage\Enums\NewsCategory;
use App\Repository\Frontpage\NewsItemRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * A piece of news the board or a committee put out. The titles and the bodies are written per language and the body is
 * markdown, which is what the website renders it as.
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
     * The English body of the news item, as markdown.
     */
    #[Column(type: Types::TEXT)]
    private string $englishContent;

    /**
     * The Dutch body of the news item, as markdown.
     */
    #[Column(type: Types::TEXT)]
    private string $dutchContent;

    /**
     * What the item is about, which is what the feed's filter narrows by.
     */
    #[Column(
        type: Types::STRING,
        enumType: NewsCategory::class,
    )]
    private NewsCategory $category = NewsCategory::Association;

    /**
     * Whether this news item is pinned to the top of the news section or not.
     */
    #[Column(type: Types::BOOLEAN)]
    private bool $pinned;

    public function getCategory(): NewsCategory
    {
        return $this->category;
    }

    public function setCategory(NewsCategory $category): void
    {
        $this->category = $category;
    }

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

    /**
     * The title in the language the site is being read in.
     */
    public function getTitle(): string
    {
        return Languages::Dutch === Languages::current()
            ? $this->dutchTitle
            : $this->englishTitle;
    }

    /**
     * The body in the language the site is being read in, as markdown.
     */
    public function getContent(): string
    {
        return Languages::Dutch === Languages::current()
            ? $this->dutchContent
            : $this->englishContent;
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
}
