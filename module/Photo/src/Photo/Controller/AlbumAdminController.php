<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AlbumAdminController extends AbstractActionController
{

    public function indexAction()
    {
        $albumService = $this->getAlbumService();
        $albums = $albumService->getAlbumTree();
        return new ViewModel(array(
            'albums' => $albums
        ));
    }

    public function createAction()
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

    public function pageAction()
    {
        
    }

    public function editAction()
    {
        
    }

    public function addAction()
    {
        
    }

    public function uploadAction()
    {
        
    }

    public function importAction()
    {
        $form = $this->getAlbumService()->getPhotoImportForm();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $album = $this->getAlbumService()->getAlbum($request->getPost()['album_id']);
                $this->getPhotoService()->storeUploadedDirectory($request->getPost()['folder_path'], $album);
            }
        }

        return new ViewModel(array(
            'form' => $form
        ));
    }

    public function moveAction()
    {
        
    }

    public function deleteAction()
    {
        
    }

    /**
     * Get the album service.
     */
    public function getAlbumService()
    {
        return $this->getServiceLocator()->get('photo_service_album');
    }

    /**
     * Get the photo service.
     */
    public function getPhotoService()
    {
        return $this->getServiceLocator()->get('photo_service_photo');
    }

}
