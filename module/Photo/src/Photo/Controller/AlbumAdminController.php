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

        $data['album'] = $data['album']->toArray();
        for ($i = 0; $i < count($data['albums']); $i++) {
            $data['albums'][$i] = $data['albums'][$i]->toArray();
        }
        for ($i = 0; $i < count($data['photos']); $i++) {
            $data['photos'][$i] = $data['photos'][$i]->toArray();
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
        $request = $this->getRequest();
        $result = array();
        $result['success'] = false;
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $this->getAlbumService()->getAlbum($albumId);

            try {
                $this->getPhotoService()->upload($request->getFiles(), $album);
                $result['success'] = true;
            } catch (\Exception $e) {
                $this->getResponse()->setStatusCode(500);
                $result['error'] = $e->getMessage();
            }
        }
        return new JsonModel($result);
    }

    public function importAction()
    {
        $request = $this->getRequest();
        $result = array();
        $result['success'] = false;
        if ($request->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $album = $this->getAlbumService()->getAlbum($albumId);
            try {
                $this->getPhotoService()->storeUploadedDirectory($request->getPost()['folder_path'], $album);
                $result['success'] = true;
            } catch (\Exception $e) {
                $this->getResponse()->setStatusCode(500);
                $result['error'] = $e->getMessage();
            }
        }
        return new JsonModel($result);
    }

    public function moveAction()
    {
        
    }

    public function deleteAction()
    {
        
    }

    /**
     * Regenerates the cover photo for the album
     */
    public function coverAction()
    {
        if ($this->getRequest()->isPost()) {
            $albumId = $this->params()->fromRoute('album_id');
            $this->getAlbumService()->generateAlbumCover($albumId);
        }
        return new JsonModel(array());
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
