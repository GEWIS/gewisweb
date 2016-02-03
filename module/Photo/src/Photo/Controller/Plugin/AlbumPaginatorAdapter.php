<?php

namespace Photo\Controller\Plugin;

/**
 * Paginator for album pages
 *
 */
class AlbumPaginatorAdapter implements \Zend\Paginator\Adapter\AdapterInterface
{

    /**
     * Album
     *
     * @var \Photo\Model\Album
     */
    protected $album = null;
    /**
     * Item count
     *
     * @var int
     */
    protected $count = null;

    /**
     * Service manager
     *
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $sm = null;

    /**
     * Constructor.
     *
     * @param \Photo\Model\Album $album Album to paginate
     */
    public function __construct($album, $sm)
    {
        $this->album = $album;
        $this->count = $album->getAlbumCount() + $album->getPhotoCount(false);
        $this->sm = $sm;
    }

    /**
     * Returns an array of items for a page.
     *
     * @param  int $offset Page offset
     * @param  int $itemCountPerPage Number of items per page
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $albumService = $this->getAlbumService();
        $photoService = $this->getPhotoService();

        $albums = $albumService->getAlbums($this->album, $offset, $itemCountPerPage);

        $photoCount = $itemCountPerPage - count($albums);
        $photoStart = max($offset - $this->album->getAlbumCount(), 0);
        $photos = $photoService->getPhotos($this->album, $photoStart, $photoCount);

        $items = array_merge($albums, $photos);

        return $items;
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

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    private function getAlbumService()
    {
        return $this->sm->get("photo_service_album");
    }

    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    private function getPhotoService()
    {
        return $this->sm->get("photo_service_photo");
    }
}