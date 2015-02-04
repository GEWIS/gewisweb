<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController
{

    public function indexAction()
    {
        
    }

    public function uploadAction()
    {
        
    }

    public function viewAlbumAction()
    {
        
    }

    public function createAlbumAction()
    {
        $service = $this->getAlbumService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            //TODO: save and create album
        }

        return new ViewModel(array(
            'form' => $service->getCreateAlbumForm()
        ));
    }

    public function albumAction()
    {
        $service = $this->getAlbumService();
        $albums = $service->getAlbumTree();
        return new ViewModel(array(
            'albums' => $albums
        ));
    }

    /**
     * Get the album service.
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get('photo_service_album');
    }

}
