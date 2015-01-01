<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $album_id = $this->params()->fromRoute('album_id');
        $activepage = (int) $this->params()->fromRoute('page');
        $album_service = $this->getAlbumService();
        $photo_service = $this->getPhotoService();
        $album = $album_service->getAlbum($album_id);
        $config = $album_service->getConfig();
        $lastpage = (int) floor(($album->getPhotoCount() + $album->getAlbumCount()) / $config['max_photos_page']);
        if ($activepage > $lastpage) {
            //TODO: throw some error, if this every occurs it's the user's fault
        }

        $albums = array();
        $album_start = $activepage * $config['max_photos_page'];
        //check if we need to display albums on this page:
        if ($album_start < $album->getAlbumCount()) {
            $albums = $album_service->getAlbums($album, $album_start, $config['max_photos_page']);
        }
        $albums = $photo_service->populateCoverPhotos($albums);
        $photos = array();
        $photo_count = $config['max_photos_page'] - count($albums);
        //check if we need to display photos on this page:
        if ($photo_count > 0) {
            $photo_start = max($activepage * $config['max_photos_page'] - $album->getAlbumCount(), 0);
            $photos = $this->getAlbumService()->getPhotos($album, $photo_start, $photo_count);
        }

        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $config['upload_dir']);

        $pages = $this->getAlbumPaging($activepage, $lastpage);
        return new ViewModel(array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos,
            'basedir' => $basedir,
            'activepage' => $activepage,
            'pages' => $pages,
            'lastpage' => $lastpage
        ));
    }

    /**
     * This fucntion determines which set of pages to show the user to
     * navigate to. The base idea is to show the two pages before and the 
     * two pages following the currently active page. With special
     * conditions for when the last and the first page are reached.
     * @param type $lastpage the last page in the album
     * @param type $activepage the page the user is currently on
     * @return array the pages to show the user
     */
    protected function getAlbumPaging($activepage, $lastpage)
    {
        $pages = array();
        $startpage = $activepage - 2;
        $endpage = $activepage + 2;
        if ($startpage < 0) {
            $endpage-=$startpage;
            $startpage = 0;
        }
        if ($endpage > $lastpage) {
            if ($startpage > 0) {
                $startpage -= min($endpage - $lastpage, $startpage);
            }
            $endpage = $lastpage;
        }
        for ($i = $startpage; $i <= $endpage; $i++) {
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
