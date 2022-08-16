<?php

namespace Photo\Controller\Plugin;

use Laminas\Paginator\Adapter\AdapterInterface;
use Photo\Model\Album as AlbumModel;
use Photo\Service\{
    Album as AlbumService,
    Photo as PhotoService,
};

/**
 * Paginator for album pages.
 */
class AlbumPaginatorAdapter implements AdapterInterface
{
    /**
     * Item count.
     *
     * @var int|null
     */
    protected ?int $count = null;

    // phpcs:ignore Gewis.General.RequireConstructorPromotion -- not possible
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
     * @param int $offset Page offset
     * @param int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems(
        $offset,
        $itemCountPerPage,
    ): array {
        $albums = $this->albumService->getAlbums(
            $this->album,
            $offset,
            $itemCountPerPage
        );

        $photoCount = $itemCountPerPage - count($albums);
        $photoStart = max($offset - $this->album->getAlbumCount(), 0);
        $photos = $this->photoService->getPhotos($this->album, $photoStart, $photoCount);

        return array_merge($albums, $photos);
    }

    /**
     * Returns the total number of rows in the array.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }
}
