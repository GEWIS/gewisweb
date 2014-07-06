<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PhotoController extends AbstractActionController {

    public function indexAction() {
        $service = $this->getAlbumService();
        $albums = $service->getAlbums();
            return new ViewModel(array(
                'albums' => $albums
            ));
            
        
    }

    /**
     * Gets the album service.
     * 
     * @return Photo\Service\Album
     */
    public function getAlbumService() {
        return $this->getServiceLocator()->get("photo_service_album");
    }

}
