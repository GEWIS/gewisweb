<?php

declare(strict_types=1);

namespace App\Twig\Components\Frontpage;

use App\Entity\Frontpage\Enums\NewsCategory;
use App\Entity\Frontpage\NewsItem;
use App\Repository\Frontpage\NewsItemRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function count;
use function iterator_to_array;

/**
 * The news archive: everything the association has put out in one association year, or in all of them, grouped into the
 * months it was written in. The year comes from the page (it is a query parameter the year switcher navigates with);
 * the category and the search box filter within it without a page reload.
 */
#[AsLiveComponent(
    name: 'Frontpage:NewsOverview',
    template: 'components/Frontpage/NewsOverview.html.twig',
)]
final class NewsOverview
{
    use DefaultActionTrait;

    public const int PAGE_SIZE = 15;

    // Set by the page, not by the reader: the year switcher is a set of links, so it reloads.
    #[LiveProp]
    public ?int $year = null;

    // Not URL-synced: the year is a query parameter of its own, which the component's own sync could drop. A link
    // to one category still lands right, because the page hands the parameter in when it mounts the component.
    #[LiveProp(writable: true)]
    public ?string $category = null;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp]
    public int $limit = self::PAGE_SIZE;

    /** @var Paginator<NewsItem>|null */
    private ?Paginator $paginator = null;

    /** @var NewsItem[]|null */
    private ?array $items = null;

    public function __construct(
        private readonly NewsItemRepository $newsItemRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    /**
     * @return NewsItem[]
     */
    public function getItems(): array
    {
        return $this->items ??= iterator_to_array(
            $this->getPaginator()->getIterator(),
            false,
        );
    }

    /**
     * The items grouped by the month they were written in, keyed 'Y-m' and in the same order as the list itself, so
     * the archive can print a divider before each group.
     *
     * @return array<string, NewsItem[]>
     */
    public function getItemsByMonth(): array
    {
        $grouped = [];
        foreach ($this->getItems() as $item) {
            $grouped[$item->getDate()->format('Y-m')][] = $item;
        }

        return $grouped;
    }

    public function getTotalCount(): int
    {
        return $this->getPaginator()->count();
    }

    public function hasMore(): bool
    {
        return $this->getTotalCount() > count($this->getItems());
    }

    /**
     * @return NewsCategory[]
     */
    public function getCategories(): array
    {
        return NewsCategory::cases();
    }

    /**
     * @return Paginator<NewsItem>
     */
    private function getPaginator(): Paginator
    {
        return $this->paginator ??= $this->newsItemRepository->findForOverview(
            year: $this->year,
            category: null !== $this->category
                ? NewsCategory::tryFrom($this->category)
                : null,
            search: $this->search,
            locale: $this->requestStack->getCurrentRequest()?->getLocale() ?? 'en',
            limit: $this->limit,
        );
    }
}
