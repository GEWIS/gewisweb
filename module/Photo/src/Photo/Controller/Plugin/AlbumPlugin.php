<?php

namespace Photo\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Paginator;
use Zend\View\Helper;

/**
 * This plugin helps with rendering the pages doing album related stuff.
 */
class AlbumPlugin extends AbstractPlugin
{

    /**
     * @var \Photo\Service\Photo
     */
    private $photoService;

    /**
     * @var \Photo\Service\Album
     */
    private $albumService;

    function __construct(\Photo\Service\Photo $photoService, \Photo\Service\Album $albumService)
    {
        $this->photoService = $photoService;
        $this->albumService = $albumService;
    }

    /**
     * Gets an album page, but returns all objects as assoc arrays
     *
     * @param int $albumId the id of the album
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbumAsArray($albumId)
    {
        $album = $this->albumService->getAlbum($albumId);

        if (is_null($album)) {
            return null;
        }

        $albumArray = $album->toArrayWithChildren();

        $photos = $albumArray['photos'];
        $albums = $albumArray['children'];

        $albumArray['photos'] = [];
        $albumArray['children'] = [];

        return [
            'album' => $albumArray,
            'basedir' => $this->photoService->getBaseDirectory(),
            'photos' => $photos,
            'albums' => $albums
        ];
    }

    /**
     * Gets an album page, but returns all objects as assoc arrays
     *
     * @param int $albumId the id of the album
     * @param int $activePage the page of the album
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbumPageAsArray($albumId, $activePage)
    {
        $page = $this->getAlbumPage($albumId, $activePage);
        if (is_null($page)) {
            return null;
        }
        $paginator = $page['paginator'];
        $photos = [];
        $albums = [];

        foreach ($paginator as $item) {
            if ($item->getResourceId() === 'album') {
                $albums[] = $item->toArray();
            } else {
                $photos[] = $item->toArray();
            }
        }

        return [
            'album' => $page['album']->toArray(),
            'basedir' => $page['basedir'],
            'pages' => $paginator->getPages(),
            'photos' => $photos,
            'albums' => $albums
        ];
    }

    /**
     * Retrieves all data needed to display a page of an album
     *
     * @param int $albumId the id of the album
     * @param int $activePage the page of the album
     * @param string $type "album"|"member"|"year"
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbumPage($albumId, $activePage, $type = 'album')
    {
        $album = $this->albumService->getAlbum($albumId, $type);
        if (is_null($album)) {
            return null;
        }
        $paginator = new Paginator\Paginator(
            new AlbumPaginatorAdapter(
                $album,
                $this->photoService,
                $this->albumService
            )
        );
        $paginator->setCurrentPageNumber($activePage);

        $config = $this->albumService->getConfig();
        $paginator->setItemCountPerPage($config['max_photos_page']);

        $basedir = $this->photoService->getBaseDirectory();

        return [
            'album' => $album,
            'basedir' => $basedir,
            'paginator' => $paginator,
        ];
    }

}
