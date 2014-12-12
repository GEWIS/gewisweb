<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PhotoController extends AbstractActionController
{

    public function indexAction()
    {
        $album_service = $this->getAlbumService();
        $albums = $album_service->getAlbums();
        $photo_service = $this->getPhotoService();


        return new ViewModel(array(
            'albums' => $albums
        ));
    }

    /**
     * Called on viewing a photo
     * 
     */
    public function viewAction()
    {
        $photo_id = $this->params()->fromRoute('photo_id');
        $photo_service = $this->getPhotoService();
        $photo = $photo_service->getPhoto($photo_id);
        $config = $photo_service->getConfig();
        //we'll fix this ugly thing later vv
        $basedir = str_replace("public", "", $config['upload_dir']);
        return new ViewModel(array(
            'photo' => $photo,
            'basedir' => $basedir
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
