<?php

declare(strict_types=1);

namespace Photo\Controller\Plugin;

use Laminas\Paginator\Adapter\AdapterInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Model\Photo as PhotoModel;
use Photo\Service\Album as AlbumService;
use Photo\Service\Photo as PhotoService;

use function array_merge;
use function count;
use function max;

/**
 * Paginator for album pages.
 *
 * @template-implements AdapterInterface<int, PhotoModel>
 */
class AlbumPaginatorAdapter implements AdapterInterface
{
    /**
     * Item count.
     */
    protected ?int $count = null;

    public function __construct(
        private readonly PhotoService $photoService,
        private readonly AlbumService $albumService,
        private readonly ?AlbumModel $album = null,
    ) {
        $this->count = $album->getAlbumCount() + $album->getPhotoCount(false);
    }

    /**
     * Returns an array of items for a page.
     *
     * @param int $offset           Page offset
     * @param int $itemCountPerPage Number of items per page
     *
     * @return array<array-key, AlbumModel|PhotoModel>
     */
    public function getItems(
        $offset,
        $itemCountPerPage,
    ): array {
        $albums = $this->albumService->getAlbums(
            $this->album,
            $offset,
            $itemCountPerPage,
        );

        $photoCount = $itemCountPerPage - count($albums);
        $photoStart = max($offset - $this->album->getAlbumCount(), 0);
        $photos = $this->photoService->getPhotos($this->album, $photoStart, $photoCount);

        return array_merge($albums, $photos);
    }

    /**
     * Returns the total number of rows in the array.
     */
    public function count(): int
    {
        return $this->count;
    }
}
