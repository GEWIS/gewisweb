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
     * @param int $activePage the page of the album
     *
     * @return array|null Array with data or null if the page does not exist
     */
    public function getAlbumPageAsArray($albumId, $activePage)
    {
        $page = $this->getAlbumPage($albumId, $activePage);
        if (is_null($page)) {
            return null;
        }
        $paginator = $page['paginator'];
        $photos = array();
        $albums = array();

        foreach ($paginator as $item) {
            if ($item->getResourceId() === 'album') {
                $albums[] = $item->toArray();
            } else {
                $photos[] = $item->toArray();
            }
        }

        return array(
            'album' => $page['album']->toArray(),
            'basedir' => $page['basedir'],
            'pages' => $paginator->getPages(),
            'photos' => $photos,
            'albums' => $albums
        );
    }

    /**
     * Retrieves all data needed to display a page of an album
     *
     * @param int $albumId the id of the album
     * @param int $activePage the page of the album
     *
     * @return array|null Array with data or null if the page does not exist
     */
    public function getAlbumPage($albumId, $activePage)
    {
        $albumService = $this->getAlbumService();
        $album = $albumService->getAlbum($albumId);
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

        return array(
            'album' => $album,
            'basedir' => $basedir,
            'paginator' => $paginator,
        );
    }

    public function getURLs()
    {
        return array(
            'album_edit' => $this->url()->fromRoute(
                'admin_photo/album_edit', array('album_id' => '{0}')
            ),
            'album_create' => $this->url()->fromRoute(
                'admin_photo/album_delete', array('album_id' => '{0}')
            ),
            'album_add' => $this->url()->fromRoute(
                'admin_photo/album_add', array('album_id' => '{0}')
            ),
            'album_move' => $this->url()->fromRoute(
                'admin_photo/album_move', array('album_id' => '{0}')
            ),
            'album_delete' => $this->url()->fromRoute(
                'admin_photo/album_delete', array('album_id' => '{0}')
            ),
            'album_create' => $this->url()->fromRoute(
                'admin_photo/album_create', array('album_id' => '{0}')
            ),
            'album_cover' => $this->url()->fromRoute(
                'admin_photo/album_cover', array('album_id' => '{0}')
            ),
            'album_page' => $this->url()->fromRoute(
                'admin_photo/album_page', array('album_id' => '{0}', 'page' => '{1}')
            ),
            'album_index' => $this->url()->fromRoute(
                'admin_photo/album_index', array('album_id' => '{0}')
            ),
            'photo_index' => $this->url()->fromRoute(
                'admin_photo/photo_index', array('photo_id' => '{0}')
            ),
            'photo_delete' => $this->url()->fromRoute(
                'admin_photo/photo_delete', array('photo_id' => '{0}')
            ),
            'photo_move' => $this->url()->fromRoute(
                'admin_photo/photo_move', array('photo_id' => '{0}')
            ),


        );
    }

    /**
     * Gets the album service.
     *
     * @return \Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getController()->getServiceLocator()->get("photo_service_album");
    }

    /**
     * Gets the photo service.
     *
     * @return \Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getController()->getServiceLocator()->get("photo_service_photo");
    }

}
