<?php

namespace Photo\Controller\Plugin;

use Laminas\Paginator\Adapter\AdapterInterface;
use Photo\Service\Album;
use Photo\Service\Photo;

/**
 * Paginator for album pages.
 */
class AlbumPaginatorAdapter implements AdapterInterface
{
    /**
     * Album.
     *
     * @var \Photo\Model\Album
     */
    protected $album = null;

    /**
     * Item count.
     *
     * @var int
     */
    protected $count = null;

    /**
     * @var Photo
     */
    private $photoService;

    /**
     * @var Album
     */
    private $albumService;

    /**
     * Constructor.
     *
     * @param \Photo\Model\Album $album Album to paginate
     */
    public function __construct(\Photo\Model\Album $album, Photo $photoService, Album $albumService)
    {
        $this->album = $album;
        $this->photoService = $photoService;
        $this->albumService = $albumService;

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
    public function getItems($offset, $itemCountPerPage)
    {
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
    public function count()
    {
        return $this->count;
    }
}
