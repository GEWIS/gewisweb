<?php

namespace Photo\Controller\Plugin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * This plugin helps with rendering the pages doing album related stuff.
 */
class AlbumPlugin extends AbstractPlugin
{

    /**
     * This function determines which set of pages to show the user to
     * navigate to. The base idea is to show the two pages before and the
     * two pages following the currently active page. With special
     * conditions for when the last and the first page are reached.
     * @param int $lastPage the last page in the album
     * @param int $activePage the page the user is currently on
     * @return array the pages to show the user
     */
    public function getAlbumPaging($activePage, $lastPage)
    {
        $pages = array();
        $startPage = $activePage - 2;
        $endPage = $activePage + 2;
        if ($startPage < 0) {
            $endPage -= $startPage;
            $startPage = 0;
        }
        if ($endPage > $lastPage) {
            if ($startPage > 0) {
                $startPage -= min($endPage - $lastPage, $startPage);
            }
            $endPage = $lastPage;
        }
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pages[] = $i;
        }

        return $pages;
    }

    /**
     * Retrieves all data needed to display a page of an album
     *
     * @param int $albumId the id of the album
     * @param int $activePage the page of the album
     * @return array|null Array with data or null if the page does not exist
     */
    public function getAlbumPage($albumId, $activePage)
    {

        $albumService = $this->getAlbumService();
        $photoService = $this->getPhotoService();
        $album = $albumService->getAlbum($albumId);
        if (is_null($album)) {
            return null;
        }
        $config = $albumService->getConfig();
        $lastpage = (int)floor(($album->getPhotoCount() + $album->getAlbumCount()) / $config['max_photos_page']);
        if ($activePage > $lastpage) {
            return null;
        }

        $albums = array();
        $albumStart = $activePage * $config['max_photos_page'];
        //check if we need to display albums on this page:
        if ($albumStart < $album->getAlbumCount()) {
            $albums = $albumService->getAlbums($album, $albumStart, $config['max_photos_page']);
        }

        $photos = array();
        $photoCount = $config['max_photos_page'] - count($albums);
        //check if we need to display photos on this page:
        if ($photoCount > 0) {
            $photo_start = max($activePage * $config['max_photos_page'] - $album->getAlbumCount(), 0);
            $photos = $photoService->getPhotos($album, $photo_start, $photoCount);
        }

        $basedir = $photoService->getBaseDirectory();

        $pages = $this->getAlbumPaging($activePage, $lastpage);

        return array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos,
            'basedir' => $basedir,
            'activepage' => $activePage,
            'pages' => $pages,
            'lastpage' => $lastpage
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
