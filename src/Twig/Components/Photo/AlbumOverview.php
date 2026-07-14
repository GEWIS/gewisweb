<?php

declare(strict_types=1);

namespace App\Twig\Components\Photo;

use App\Entity\Photo\Album;
use App\Service\Photo\AlbumService;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The albums of one association year on the photo landing page, grouped into months. The year is chosen by the page
 * (the year-switcher navigates here with a fresh year), and search filters the albums by name without a page reload.
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

    #[LiveProp(writable: true)]
    public string $search = '';

    public function __construct(
        private readonly AlbumService $albumService,
    ) {
    }

    /**
     * @return array<string, Album[]>
     */
    public function getAlbumsByMonth(): array
    {
        if (null === $this->year) {
            return [];
        }

        return $this->albumService->getViewableRootAlbumsByMonth(
            $this->year,
            '' === $this->search ? null : $this->search,
        );
    }
}
