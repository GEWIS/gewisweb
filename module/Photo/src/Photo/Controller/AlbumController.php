<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumController extends AbstractActionController
{

    public function indexAction()
    {
        $album_id=$this->params()->fromRoute('album');
        $album_service = $this->getAlbumService();
        $album = $album_service->getAlbum($id);
        $albums = $album_service->getAlbums($album);
        //$photo_service = $this->getPhotoService();
        $photos = $album_service->getPhotos($album);
        return new ViewModel(array(
            'album' => $album,
            'albums' => $albums,
            'photos' => $photos
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
