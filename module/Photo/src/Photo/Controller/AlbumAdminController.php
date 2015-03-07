<?php

namespace Photo\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class AlbumAdminController extends AbstractActionController
{

    public function indexAction()
    {
        $albumService = $this->getAlbumService();
        $albums = $albumService->getAlbums();
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
        $albumId = $this->params()->fromRoute('album_id');
        $activePage = (int) $this->params()->fromRoute('page');
        $data = $this->AlbumPlugin()->getAlbumPage($albumId, $activePage);
        
        //TODO: Fix these ugly hacks below this line!!
        for ($i = 0; $i < count($data['albums']); $i++) {
            $data['albums'][$i] = (array) $data['albums'][$i];
        }
        for ($i = 0; $i < count($data['photos']); $i++) {
            $data['photos'][$i] = (array) $data['photos'][$i];
        }
        return new JsonModel($data);
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
