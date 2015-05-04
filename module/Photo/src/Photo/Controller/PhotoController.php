<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PhotoController extends AbstractActionController
{

    public function indexAction()
    {
        $albums = $this->getAlbumService()->getAlbums();
        //add any other special behavior which is required for the main photo page here later
        //we'll fix this ugly thing later vv
        $config = $this->getPhotoService()->getConfig();
        $basedir = str_replace("public", "", $config['upload_dir']);
        return new ViewModel(array(
            'albums' => $albums,
            'basedir' => $basedir
        ));
    }

    /**
     * Called on viewing a photo
     * 
     */
    public function viewAction()
    {
        $photoId = $this->params()->fromRoute('photo_id');
        $photoData = $this->getPhotoService()->getPhotoData($photoId);

        if (is_null($photoData)) {
            return $this->notFoundAction();
        }

        return new ViewModel($photoData);
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
