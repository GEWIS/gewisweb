<?php

declare(strict_types=1);

namespace App\Twig\Components\Photo;

use App\Entity\Photo\Album;
use App\Service\Photo\AlbumService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_merge;
use function array_values;

/**
 * The albums shown on the photo landing page and the cross-year search page, grouped into months. On the landing page
 * the year is chosen by the page and search filters within it; on the search page (`crossYear`) the query searches
 * every year's albums by name. Filtering happens without a page reload.
 */
#[AsLiveComponent(
    name: 'Photo:AlbumOverview',
    template: 'components/Photo/AlbumOverview.html.twig',
)]
final class AlbumOverview
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?int $year = null;

    #[LiveProp]
    public bool $crossYear = false;

    // Not URL-synced: on the landing page the year is a query param, and syncing search could drop it on reload.
    #[LiveProp(writable: true)]
    public string $search = '';

    /** @var array<string, Album[]>|null */
    private ?array $albumsByMonth = null;

    public function __construct(
        private readonly AlbumService $albumService,
    ) {
    }

    /**
     * @return array<string, Album[]>
     */
    public function getAlbumsByMonth(): array
    {
        if (null !== $this->albumsByMonth) {
            return $this->albumsByMonth;
        }

        if ($this->crossYear) {
            // Wait for a query before searching: the whole photo archive is too large to list at once.
            return $this->albumsByMonth = '' === $this->search
                ? []
                : $this->albumService->searchViewableAlbums($this->search);
        }

        if (null === $this->year) {
            return $this->albumsByMonth = [];
        }

        return $this->albumsByMonth = $this->albumService->getViewableRootAlbumsByMonth(
            $this->year,
            '' === $this->search ? null : $this->search,
        );
    }

    /**
     * The sub-album and photo counts the album cards need, batched so the grid does not issue a COUNT per card.
     *
     * @return array{subAlbums: array<int, int>, photos: array<int, int>}
     */
    public function getCardCounts(): array
    {
        $grouped = $this->getAlbumsByMonth();
        if ([] === $grouped) {
            return [
                'subAlbums' => [],
                'photos' => [],
            ];
        }

        return $this->albumService->getCardCounts(
            array_merge(...array_values($grouped)),
            true,
        );
    }
}
