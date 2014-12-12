<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $album_id = $this->params()->fromRoute('album_id');
        $page = $this->params()->fromRoute('page');
        $album_service = $this->getAlbumService();
        $album = $album_service->getAlbum($album_id);
        $albums = $album_service->getAlbums($album);
        $photos = $album_service->getPhotos($album, $page);
        $config = $album_service->getConfig();
        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $config['upload_dir']);
        return new ViewModel(array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos,
            'basedir' => $basedir,
            'page' => $page
        ));
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
