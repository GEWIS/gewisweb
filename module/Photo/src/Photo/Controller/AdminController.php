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

    public function importFolderAction()
    {
        $form = $this->getAlbumService()->getPhotoImportForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                var_dump($request->getPost()['folder_path']);
                $album = $this->getAlbumService()->getAlbum($request->getPost()['album_id']);
                $this->getAlbumService()->storeUploadedDirectory($request->getPost()['folder_path'], $album);
            }
        }

        return new ViewModel(array(
            'form' => $form
        ));
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
