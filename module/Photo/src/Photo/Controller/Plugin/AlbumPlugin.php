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
     * Gets an album page, but returns all objects as assoc arrays
     *
     * @param int $albumId the id of the album
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbumAsArray($albumId)
    {
        $albumService = $this->getAlbumService();
        $album = $albumService->getAlbum($albumId);
        
        if (is_null($album)) {
            return null;
        }
        
        $albumArray = $album->toArrayWithChildren();
        
        $photos = $albumArray['photos'];
        $albums = $albumArray['children'];
        
        $albumArray['photos'] = [];
        $albumArray['children'] = [];
        
        $photoService = $this->getPhotoService();
        
        return [
            'album'   => $albumArray,
            'basedir' => $photoService->getBaseDirectory(),
            'photos'  => $photos,
            'albums'  => $albums
        ];
    }
    
    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getController()->getServiceLocator()
            ->get("photo_service_album");
    }
    
    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getController()->getServiceLocator()
            ->get("photo_service_photo");
    }
    
    /**
     * Gets an album page, but returns all objects as assoc arrays
     *
     * @param int $albumId    the id of the album
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
            'album'   => $page['album']->toArray(),
            'basedir' => $page['basedir'],
            'pages'   => $paginator->getPages(),
            'photos'  => $photos,
            'albums'  => $albums
        ];
    }

    /**
     * Retrieves all data needed to display a page of an album
     *
     * @param int    $albumId    the id of the album
     * @param int    $activePage the page of the album
     * @param string $type       "album"|"member"|"year"
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbumPage($albumId, $activePage, $type = 'album')
    {
        $albumService = $this->getAlbumService();
        $album = $albumService->getAlbum($albumId, $type);
        if (is_null($album)) {
            return null;
        }
        $paginator = new Paginator\Paginator(
            new AlbumPaginatorAdapter(
                $album,
                $this->getController()->getServiceLocator()
            )
        );
        $paginator->setCurrentPageNumber($activePage);

        $config = $albumService->getConfig();
        $paginator->setItemCountPerPage($config['max_photos_page']);

        $photoService = $this->getPhotoService();
        $basedir = $photoService->getBaseDirectory();

        return [
            'album'     => $album,
            'basedir'   => $basedir,
            'paginator' => $paginator,
        ];
    }

    /**
     * Retrieves all data needed to display the entire album
     *
     * @param int    $albumId    the id of the album
     * @param string $type       "album"|"member"|"year"
     *
     * @return array|null Array with data or null if the page does not exist
     * @throws \Exception
     */
    public function getAlbum($albumId, $type = 'album')
    {
        $albumService = $this->getAlbumService();
        $album = $albumService->getAlbum($albumId, $type);
        if (is_null($album)) {
            return null;
        }
        $paginator = new Paginator\Paginator(
            new AlbumPaginatorAdapter(
                $album,
                $this->getController()->getServiceLocator()
            )
        );
        $paginator->setCurrentPageNumber($activePage);

        $config = $albumService->getConfig();
        $paginator->setItemCountPerPage($config['max_photos_page']);

        $photoService = $this->getPhotoService();
        $basedir = $photoService->getBaseDirectory();

        return [
            'album'     => $album,
            'basedir'   => $basedir,
            'paginator' => $paginator,
        ];
    }
    
}
