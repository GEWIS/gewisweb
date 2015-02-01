<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');
        $albumService = $this->getAlbumService();
        $album = $albumService->getAlbum($albumId);
        if (is_null($album)) {
            $this->getResponse()->setStatusCode(404);
            return;
        }
        $config = $albumService->getConfig();
        $lastpage = (int) floor(($album->getPhotoCount() + $album->getAlbumCount()) / $config['max_photos_page']);
        if ($activePage > $lastpage) {
            $this->getResponse()->setStatusCode(404);
            return;
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
            $photos = $this->getPhotoService()->getPhotos($album, $photo_start, $photoCount);
        }

        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $config['upload_dir']);

        $pages = $this->getAlbumPaging($activePage, $lastpage);
        return new ViewModel(array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos,
            'basedir' => $basedir,
            'activepage' => $activePage,
            'pages' => $pages,
            'lastpage' => $lastpage
        ));
    }

    /**
     * This fucntion determines which set of pages to show the user to
     * navigate to. The base idea is to show the two pages before and the 
     * two pages following the currently active page. With special
     * conditions for when the last and the first page are reached.
     * @param type $lastPage the last page in the album
     * @param type $activePage the page the user is currently on
     * @return array the pages to show the user
     */
    protected function getAlbumPaging($activePage, $lastPage)
    {
        $pages = array();
        $startPage = $activePage - 2;
        $endPage = $activePage + 2;
        if ($startPage < 0) {
            $endPage-=$startPage;
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
     * Gets the album service.
     * 
     * @return Photo\Service\Album
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get("photo_service_album");
    }

    /**
     * Gets the photo service.
     * 
     * @return Photo\Service\Photo
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get("photo_service_photo");
    }

}
